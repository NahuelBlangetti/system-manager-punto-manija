<?php

namespace Tests\Feature;

use App\Filament\Pages\CrearVenta;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Comprobante;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Tickets\FacturaTicketEscPosBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FacturacionArcaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    private int $ultimo = 7;

    /** @var list<string> */
    private array $respuestasCae = ['A'];

    private ?array $consultado = null;

    private string $certPath = '';

    private string $keyPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create();
        $this->actingAs($this->user);

        CashRegister::create([
            'user_id' => $this->user->id,
            'opening_amount' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $this->product = Product::create([
            'category_id' => Category::create(['name' => 'Bebidas', 'slug' => 'bebidas'])->id,
            'name' => 'Fernet 750',
            'sale_price' => 3500,
            'stock' => 20,
            'unit' => 'unidad',
        ]);

        $dir = storage_path('app/arca');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->certPath = $dir.'/testing-certificado.crt';
        $this->keyPath = $dir.'/testing-clave.key';
        file_put_contents($this->certPath, 'certificado-de-prueba');
        file_put_contents($this->keyPath, 'clave-de-prueba');

        config([
            'arca.habilitada' => true,
            'arca.entorno' => 'produccion',
            'arca.cuit' => '20123456789',
            'arca.punto_venta' => 3,
            'arca.certificado' => 'arca/testing-certificado.crt',
            'arca.clave' => 'arca/testing-clave.key',
            'arca.emisor.razon_social' => '',
            'arca.emisor.domicilio' => '',
        ]);

        Cache::put('arca:ta:produccion:'.config('arca.cuit'), ['token' => 'TOKEN', 'sign' => 'SIGN'], now()->addHour());

        Http::fake(fn (Request $request) => $this->arcaSimulado($request));
    }

    protected function tearDown(): void
    {
        if ($this->certPath !== '') {
            @unlink($this->certPath);
        }

        if ($this->keyPath !== '') {
            @unlink($this->keyPath);
        }

        parent::tearDown();
    }

    private function arcaSimulado(Request $request)
    {
        $accion = trim($request->header('SOAPAction')[0] ?? '', '"');
        $metodo = substr($accion, strrpos($accion, '/') + 1);

        $cuerpo = match ($metodo) {
            'FECompUltimoAutorizado' => "<FECompUltimoAutorizadoResult><PtoVta>3</PtoVta><CbteTipo>11</CbteTipo><CbteNro>{$this->ultimo}</CbteNro></FECompUltimoAutorizadoResult>",
            'FECompConsultar' => $this->consultado
                ? "<FECompConsultarResult><ResultGet><CbteDesde>{$this->consultado['numero']}</CbteDesde><CbteFch>".now()->format('Ymd')."</CbteFch><ImpTotal>{$this->consultado['importe']}</ImpTotal><CodAutorizacion>76543210987654</CodAutorizacion><FchVto>".now()->addDays(10)->format('Ymd').'</FchVto></ResultGet></FECompConsultarResult>'
                : '<FECompConsultarResult><Errors><Err><Code>602</Code><Msg>No existen datos</Msg></Err></Errors></FECompConsultarResult>',
            'FECAESolicitar' => $this->respuestaCae($request),
            default => '<ok/>',
        };

        if ($cuerpo === null) {
            return Http::failedConnection();
        }

        return Http::response('<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><'.$metodo.'Response xmlns="http://ar.gov.afip.dif.FEV1/">'.$cuerpo.'</'.$metodo.'Response></soap:Body></soap:Envelope>');
    }

    private function respuestaCae(Request $request): ?string
    {
        $respuesta = array_shift($this->respuestasCae) ?? 'A';
        preg_match('/<ar:CbteDesde>(\d+)</', $request->body(), $m);
        $numero = (int) ($m[1] ?? 0);

        if ($respuesta === 'timeout') {
            $this->ultimo = $numero;

            return null;
        }

        if ($respuesta === 'A') {
            $this->ultimo = $numero;

            return '<FECAESolicitarResult><FeCabResp><Resultado>A</Resultado></FeCabResp><FeDetResp><FECAEDetResponse><Resultado>A</Resultado><CbteDesde>'.$numero.'</CbteDesde><CAE>12345678901234</CAE><CAEFchVto>'.now()->addDays(10)->format('Ymd').'</CAEFchVto></FECAEDetResponse></FeDetResp></FECAESolicitarResult>';
        }

        return '<FECAESolicitarResult><FeCabResp><Resultado>R</Resultado></FeCabResp><FeDetResp><FECAEDetResponse><Resultado>R</Resultado><CAE></CAE></FECAEDetResponse></FeDetResp><Errors><Err><Code>'.$respuesta.'</Code><Msg>Error simulado '.$respuesta.'</Msg></Err></Errors></FECAESolicitarResult>';
    }

    private function cobrar(bool $factura)
    {
        return Livewire::test(CrearVenta::class)
            ->call('addToCart', $this->product->id)
            ->call('addToCart', $this->product->id)
            ->set('paymentMethod', 'cash')
            ->set('facturaArca', $factura)
            ->set('printTicket', false)
            ->call('confirmSale');
    }

    public function test_cobrar_con_factura_arca_emite_la_factura_c(): void
    {
        $this->cobrar(true)
            ->assertSet('facturaNumero', '00003-00000008')
            ->assertSet('lastSaleNumber', Sale::sole()->sale_number);

        $factura = Sale::sole()->factura;
        $this->assertSame(8, (int) $factura->numero);
        $this->assertSame('12345678901234', $factura->cae);
        $this->assertSame('7000.00', $factura->importe_total);

        Http::assertSent(fn (Request $r) => str_contains($r->body(), '<ar:FECAESolicitar>')
            && str_contains($r->body(), '<ar:ImpTotal>7000.00</ar:ImpTotal>')
            && str_contains($r->body(), '<ar:CbteTipo>11</ar:CbteTipo>')
            && str_contains($r->body(), '<ar:PtoVta>3</ar:PtoVta>')
            && str_contains($r->body(), '<ar:DocTipo>99</ar:DocTipo>')
            && str_contains($r->body(), '<ar:CondicionIVAReceptorId>5</ar:CondicionIVAReceptorId>'));
    }

    public function test_sin_factura_no_consulta_arca(): void
    {
        $this->cobrar(false)->assertSet('facturaNumero', null);

        Http::assertNothingSent();
        $this->assertSame(0, Comprobante::count());
        $this->assertSame(18, $this->product->fresh()->stock);
    }

    public function test_si_arca_rechaza_la_venta_queda_cobrada_y_se_puede_reintentar(): void
    {
        $this->respuestasCae = ['10013', 'A'];

        $pos = $this->cobrar(true)
            ->assertSet('facturaNumero', null)
            ->assertSee('Reintentar factura ARCA');

        $sale = Sale::sole();
        $this->assertSame('completed', $sale->status);
        $this->assertNull($sale->factura);
        $this->assertSame(Comprobante::RECHAZADO, Comprobante::sole()->estado);

        $pos->call('facturar')->assertSet('facturaNumero', '00003-00000008');
        $this->assertSame('12345678901234', $sale->fresh()->factura->cae);
    }

    public function test_si_otro_equipo_facturo_en_el_medio_toma_el_numero_siguiente(): void
    {
        $this->respuestasCae = ['10016', 'A'];

        $this->cobrar(true);

        $this->assertSame(8, (int) Sale::sole()->factura->numero);
        $this->assertSame(0, Comprobante::where('estado', '!=', Comprobante::AUTORIZADO)->count());
    }

    public function test_si_no_llega_la_respuesta_el_reintento_recupera_la_factura_sin_duplicarla(): void
    {
        $this->respuestasCae = ['timeout'];

        $pos = $this->cobrar(true)->assertSet('facturaNumero', null);
        $this->assertSame(Comprobante::ERROR, Comprobante::sole()->estado);

        $this->consultado = ['numero' => 8, 'importe' => '7000.00'];
        $pos->call('facturar')->assertSet('facturaNumero', '00003-00000008');

        $this->assertSame('76543210987654', Sale::sole()->factura->cae);
        $this->assertSame(1, collect(Http::recorded())->filter(fn ($par) => str_contains($par[0]->body(), '<ar:FECAESolicitar>'))->count());
    }

    public function test_el_ticket_fiscal_incluye_cae_y_qr_raster(): void
    {
        $this->cobrar(true);
        $factura = Sale::sole()->factura;
        $ticket = app(FacturaTicketEscPosBuilder::class)->buildFactura($factura);

        $this->assertStringContainsString('FACTURA C', $ticket);
        $this->assertStringContainsString('Nro: 00003-00000008', $ticket);
        $this->assertStringContainsString('CUIT: 20-12345678-9', $ticket);
        $this->assertStringContainsString('CAE: 12345678901234', $ticket);
        $this->assertStringContainsString('A: Consumidor Final', $ticket);
        $this->assertStringContainsString('Punto Manija', $ticket);
        $this->assertStringContainsString("\x1Dv0", $ticket);

        $qr = json_decode(base64_decode(substr($factura->urlQr(), strlen('https://www.afip.gob.ar/fe/qr/?p='))), true);
        $this->assertSame(8, $qr['nroCmp']);
        $this->assertSame(11, $qr['tipoCmp']);
        $this->assertSame(12345678901234, $qr['codAut']);
        $this->assertSame(20123456789, $qr['cuit']);
    }

    public function test_con_arca_deshabilitada_no_se_ofrece_la_factura(): void
    {
        config(['arca.habilitada' => false]);

        Livewire::test(CrearVenta::class)
            ->call('addToCart', $this->product->id)
            ->assertDontSee('Factura ARCA')
            ->assertDontSee('Facturación electrónica no configurada');
    }

    public function test_sin_cuit_ni_certificado_la_venta_sigue_y_avisa(): void
    {
        config([
            'arca.habilitada' => true,
            'arca.cuit' => '',
            'arca.punto_venta' => 0,
            'arca.certificado' => 'arca/no-existe.crt',
            'arca.clave' => 'arca/no-existe.key',
        ]);

        Livewire::test(CrearVenta::class)
            ->call('addToCart', $this->product->id)
            ->assertDontSee('Factura ARCA')
            ->assertSee('Facturación electrónica no configurada')
            ->set('paymentMethod', 'cash')
            ->set('printTicket', false)
            ->call('confirmSale')
            ->assertSet('facturaNumero', null);

        Http::assertNothingSent();
        $this->assertSame(0, Comprobante::count());
        $this->assertSame('completed', Sale::sole()->status);
        $this->assertSame(19, $this->product->fresh()->stock);
    }

    public function test_en_ventas_se_puede_facturar_una_venta_que_no_se_facturo(): void
    {
        $this->cobrar(false);
        $sale = Sale::sole();

        Livewire::test(ListSales::class)
            ->assertTableActionHidden('printFactura', $sale)
            ->assertTableActionVisible('facturarArca', $sale)
            ->callTableAction('facturarArca', $sale)
            ->assertNotified('Factura C 00003-00000008 autorizada por ARCA');

        $this->assertSame(8, (int) $sale->fresh()->factura->numero);
    }

    public function test_en_ventas_si_arca_rechaza_avisa_y_no_imprime(): void
    {
        $this->respuestasCae = ['10013'];
        $this->cobrar(false);
        $sale = Sale::sole();

        Livewire::test(ListSales::class)
            ->callTableAction('facturarArca', $sale)
            ->assertNotified('Sin factura de ARCA');

        $this->assertNull($sale->fresh()->factura);
    }

    public function test_una_venta_ya_facturada_se_puede_reimprimir_y_no_volver_a_pedir(): void
    {
        $this->cobrar(true);
        $sale = Sale::sole();
        $pedidos = collect(Http::recorded())->filter(fn ($par) => str_contains($par[0]->body(), '<ar:FECAESolicitar>'))->count();

        Livewire::test(ListSales::class)
            ->assertTableActionVisible('printFactura', $sale)
            ->assertTableActionHidden('facturarArca', $sale)
            ->callTableAction('printFactura', $sale)
            ->assertDispatched('print-escpos-ticket');

        $this->assertSame($pedidos, collect(Http::recorded())->filter(fn ($par) => str_contains($par[0]->body(), '<ar:FECAESolicitar>'))->count());
    }
}
