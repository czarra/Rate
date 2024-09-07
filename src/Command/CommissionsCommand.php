<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\CountryByBinInterface;
use App\Service\RateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:commission')]
class CommissionsCommand extends Command
{
    use LockableTrait;

    private array $rates = [];

    private const EU_COUNTRIES_2 = [
        'AT',
        'BE',
        'BG',
        'CY',
        'CZ',
        'DE',
        'DK',
        'EE',
        'ES',
        'FI',
        'FR',
        'GR',
        'HR',
        'HU',
        'IE',
        'IT',
        'LT',
        'LU',
        'LV',
        'MT',
        'NL',
        'PL',
        'PT',
        'RO',
        'SE',
        'SI',
        'SK',
    ];

    public function __construct(
        private readonly CountryByBinInterface $countryByBin,
        private readonly RateInterface $rate,
        private readonly LoggerInterface $logger
    ){
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock($this->getName())) {
            $output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }
        $this->logger->info('Run command app:commission');

        $file = $input->getArgument('file');
        $path = __DIR__ . '/' . $file;
        if(!file_exists($path)){
            $this->logger->critical(
                sprintf('Path or File %s not exist', $path)
            );
            $this->release();
            return Command::FAILURE;
        }

        $fOpen = fopen($path, 'r');
        $lines = $this->getLines($fOpen);

        $responseData = [];
        foreach ($lines as $line) {
            try {
                $responseData[] = $this->calculation($line);
            } catch (\Throwable $e){
                $this->logger->critical($e->getMessage());
                fclose($fOpen);
                $this->release();
                return Command::FAILURE;
            }
        }
        fclose($fOpen);

        $output->writeln([
            sprintf('<comment>%s</comment>', implode(', ', $responseData)),
        ]);

        $this->logger->info('Finish command app:commission');
        $this->release();
        return Command::SUCCESS;
    }

    /**
     * @param $line
     * @return float
     * @throws \Exception
     */
    private function calculation($line): float
    {
        $objectData = json_decode($line);
        if (!isset($objectData->bin) || !isset($objectData->amount) || !isset($objectData->currency)) {
            throw new \Exception('File with wrong format!');
        }

        $objectAmount = (float)$objectData->amount;

        if ($objectData->currency !== 'EUR') {
            $rate = $this->getRates($objectData->currency);
            if (is_null($rate) || $rate < 0) {
                throw new \Exception(
                    'Wrong currency ' . (empty($objectData->currency) ? 'EMPTY' : $objectData->currency)
                );
            }
            if ($rate == 0) {
                $amountFixed = $objectAmount;
            } else {
                $amountFixed = $objectAmount / $rate;
            }
        } else {
            $amountFixed = $objectAmount;
        }
        $amount = $amountFixed * ($this->isEU($this->countryByBin->getCountry($objectData->bin)) ? 0.01 : 0.02);

        return ceil($amount * 100) / 100;
    }

    /**
     * @param $file
     * @return \Generator
     */
    private function getLines($file): \Generator
    {
        while ($line = fgets($file)) {
            yield $line;
        }
    }

    /**
     * @param string $country
     * @return bool
     */
    private function isEu(string $country): bool
    {
        return in_array($country, self::EU_COUNTRIES_2);
    }

    /**
     * @param string $currency
     * @return float|null
     */
    private function getRates(string $currency): ?float
    {
        if (isset($this->rates[$currency])) {
            return $this->rates[$currency];
        }

        $this->rates = $this->rate->getRates();

        return $this->rates[$currency] ?? null;
    }

}
