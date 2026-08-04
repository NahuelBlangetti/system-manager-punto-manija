<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\User;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TopProducts extends TableWidget
{
    protected static ?string $heading = 'Más vendidos (últimos 30 días)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public function table(Table $table): Table
    {
        $since = Carbon::now()->subDays(30);

        return $table
            ->query(
                Product::query()
                    ->with('category')
                    ->where('active', true)
                    ->withSum(['saleItems as units_sold' => function ($query) use ($since) {
                        $query->whereHas('sale', fn ($sale) => $sale
                            ->where('status', 'completed')
                            ->where('created_at', '>=', $since));
                    }], 'quantity')
                    ->orderByDesc('units_sold')
                    ->limit(8)
            )
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->size(48)
                    ->defaultImageUrl(asset('images/punto-manija-mascot.png'))
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('units_sold')
                    ->label('Vendidos')
                    ->sortable()
                    ->badge()
                    ->placeholder('Sin ventas')
                    ->formatStateUsing(fn ($state) => (int) $state)
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('sale_price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),
            ])
            ->defaultSort('units_sold', 'desc')
            ->paginated(false);
    }
}
