<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\User;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class TopProducts extends TableWidget
{
    protected static ?string $heading = 'Productos destacados';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with('category')
                    ->where('active', true)
                    ->orderByDesc('stock')
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

                TextColumn::make('sale_price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),

                TextColumn::make('cost_price')
                    ->label('Costo')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->color('gray'),

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
            ->defaultSort('stock', 'desc')
            ->paginated(false);
    }
}
