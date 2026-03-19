<?php
namespace App\Service;

class ShippingCalculator {
    private array $transporters = [
        ['name' => 'PrestaColis (S)', 'maxWeight' => 4, 'maxDims' => [15,50,30], 'price' => 2.99, 'days' => 5],
        ['name' => 'PrestaColis (M)', 'maxWeight' => 10, 'maxDims' => [40,60,60], 'price' => 3.99, 'days' => 5],
        ['name' => 'PrestaColis (L)', 'maxWeight' => 25, 'maxDims' => [100,70,70], 'price' => 5.99, 'days' => 5],
        ['name' => 'MaigaLour', 'maxWeight' => 70, 'maxDims' => [150,100,100], 'price' => 10.99, 'days' => 3],
        ['name' => 'MaigaLour Premium', 'maxWeight' => 300, 'maxDims' => [200,200,200], 'price' => 21.99, 'days' => 1],
        ['name' => 'Express24', 'maxWeight' => 20, 'maxDims' => [80,80,80], 'price' => 15.99, 'days' => 1],
        ['name' => 'EcoTransport', 'maxWeight' => 20, 'maxDims' => [80,80,80], 'price' => 7.99, 'days' => 7],
    ];

    /**
     * Get the first transporter matching weight and optionally delivery days.
     */
    public function calculate(float $totalWeight, ?int $maxDays = null): ?array {
        foreach ($this->transporters as $t) {
            if ($totalWeight <= $t['maxWeight'] && ($maxDays === null || $t['days'] <= $maxDays)) {
                return $t;
            }
        }
        return null;
    }
    
    public function getAllTransporters(): array {
        return $this->transporters;
    }
}
