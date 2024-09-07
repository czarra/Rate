<?php
declare(strict_types=1);

namespace App\Service;

interface CountryByBinInterface
{
    /**
     * @param string $bin
     * @return string
     */
    public function getCountry(string $bin): string;
}
