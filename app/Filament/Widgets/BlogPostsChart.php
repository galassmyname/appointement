<?php
 
namespace App\Filament\Widgets;
 
use Filament\Widgets\ChartWidget;
 
class BlogPostsChart extends ChartWidget
{
    protected static ?string $heading = 'Taux de changement des nominations par mois';
    protected static string $color = 'info';
    protected static ?string $pollingInterval = '3s';
    protected static bool $isLazy = \true;
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'rendez-vous créés',
                    'data' => [0, 10, 5, 2, 21, 32, 45, 74, 65, 45, 77, 89],
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => ['Jan', 'Fev', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Dec'],
        ];
    }
 
    protected function getType(): string
    {
        return 'line';
    }
}