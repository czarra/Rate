<?php

namespace App\Tests\Command;

use App\Command\CommissionsCommand;
use App\Exception\CountryByBinException;
use App\Exception\RateException;
use App\Service\CountryByBinInterface;
use App\Service\RateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;

class CommissionsCommandTest extends KernelTestCase
{
    /** @var CountryByBinInterface&MockObject */
    private $countryByBin;

    /** @var RateInterface&MockObject */
    private $rate;

    /** @var LoggerInterface&MockObject */
    private $log;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->countryByBin = $this->createMock(CountryByBinInterface::class);
        $this->rate = $this->createMock(RateInterface::class);
        $this->log = $this->createMock(LoggerInterface::class);

        $kernel = static::createKernel();
        $kernel->boot();

        $app = new Application($kernel);
        $app->add(new CommissionsCommand(
            $this->countryByBin,
            $this->rate,
            $this->log,
        ));
        $command = $app->find('app:commission');
        $this->commandTester = new CommandTester($command);
    }

    public function testWithCorrectResponse(): void
    {
        $this->countryByBin
            ->method('getCountry')
            ->withConsecutive(
                [
                    "45717360"
                ],
                [
                    "516793"
                ],
                [
                    "45417360"
                ],
                [
                    "41417360"
                ],
                [
                    "4745030"
                ],
            )
            ->willReturnOnConsecutiveCalls(
                "DK",
                "LT",
                "JP",
                "US",
                "GB",
            );

        $this->rate
            ->expects($this->once())
            ->method('getRates')
            ->willReturn(
                [
                    "USD" => 1.111049,
                    "JPY" => 159.384419,
                    "GBP" => 0.842948,
                ]
            );

        $this->log->expects($this->exactly(2))->method('info');
        $this->log->expects($this->never())->method('critical');

        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputTest.txt']);

        $this->assertSame($execute,Command::SUCCESS);

        $this->assertStringContainsString('1, 0.46, 1.26, 2.35, 47.46', $this->commandTester->getDisplay());
    }

    public function testWithCorrectResponseWithRateZeroGBP(): void
    {
        $this->countryByBin
            ->method('getCountry')
            ->withConsecutive(
                [
                    "45717360"
                ],
                [
                    "516793"
                ],
                [
                    "45417360"
                ],
                [
                    "41417360"
                ],
                [
                    "4745030"
                ],
            )
            ->willReturnOnConsecutiveCalls(
                "DK",
                "LT",
                "JP",
                "US",
                "GB",
            );

        $this->rate
            ->expects($this->once())
            ->method('getRates')
            ->willReturn(
                [
                    "USD" => 1.111049,
                    "JPY" => 159.384419,
                    "GBP" => 0,
                ]
            );

        $this->log->expects($this->exactly(2))->method('info');
        $this->log->expects($this->never())->method('critical');

        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputTest.txt']);

        $this->assertSame($execute,Command::SUCCESS);

        $this->assertStringContainsString('1, 0.46, 1.26, 2.35, 40', $this->commandTester->getDisplay());
    }

    public function testIncorrectDataInFile(): void
    {
        $this->countryByBin
            ->expects($this->exactly(2))
            ->method('getCountry')
            ->withConsecutive(
                [
                    "45717360"
                ],
                [
                    "516793"
                ],
                [
                    "45417360"
                ],
                [
                    "41417360"
                ],
                [
                    "4745030"
                ],
            )
            ->willReturnOnConsecutiveCalls(
                "DK",
                "LT",
                "JP",
                "US",
                "GB",
            );

        $this->rate
            ->expects($this->once())
            ->method('getRates')
            ->willReturn(
                [
                    "USD" => 1.111049,
                    "JPY" => 159.384419,
                    "GBP" => 0.842948,
                ]
            );
        $this->log->expects($this->once())->method('info');
        $this->log->expects($this->once())->method('critical')
            ->with('File with wrong format!');
        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputIncorrectTest.txt']);

        $this->assertSame($execute,Command::FAILURE);
    }

    public function testIncorrectDataInFileEmptyBinAmountCurrency(): void
    {
        $this->countryByBin
            ->expects($this->exactly(1))
            ->method('getCountry')
            ->withConsecutive(
                [
                    "45717360"
                ],
                [
                    "516793"
                ],
                [
                    "45417360"
                ],
                [
                    "41417360"
                ],
                [
                    "4745030"
                ],
            )
            ->willReturnOnConsecutiveCalls(
                "DK",
                "LT",
                "JP",
                "US",
                "GB",
            );

        $this->rate
            ->expects($this->once())
            ->method('getRates')
            ->willReturn(
                [
                    "USD" => 1.111049,
                    "JPY" => 159.384419,
                    "GBP" => 0.842948,
                ]
            );

        $this->log->expects($this->once())->method('info');
        $this->log->expects($this->once())->method('critical')
            ->with('Wrong currency EMPTY');
        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputIncorrect2Test.txt']);

        $this->assertSame($execute,Command::FAILURE);
    }

    public function testRateThrowException(): void
    {
        $message = 'Error Message';
        $this->countryByBin
            ->expects($this->once())
            ->method('getCountry')
            ->with("45717360")
            ->willReturn("DK");

        $this->rate
            ->expects($this->once())
            ->method('getRates')
            ->willThrowException(new RateException($message));

        $this->log->expects($this->once())->method('info');
        $this->log->expects($this->once())->method('critical')->with($message);

        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputTest.txt']);

        $this->assertSame($execute,Command::FAILURE);
    }

    public function testCountryByBinThrowException(): void
    {
        $message = 'Error Message';
        $this->countryByBin
            ->expects($this->once())
            ->method('getCountry')
            ->willThrowException(new CountryByBinException($message));

        $this->rate
            ->expects($this->never())
            ->method('getRates');


        $this->log->expects($this->once())->method('info');
        $this->log->expects($this->once())->method('critical')->with($message);

        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputTest.txt']);

        $this->assertSame($execute,Command::FAILURE);
    }

    public function testEmptyFile(): void
    {
        $this->countryByBin
            ->expects($this->never())
            ->method('getCountry');


        $this->rate
            ->expects($this->never())
            ->method('getRates');

        $this->log->expects($this->exactly(2))->method('info');
        $this->log->expects($this->never())->method('critical');
        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputEmptyTest.txt']);

        $this->assertSame($execute,Command::SUCCESS);
    }

    public function testIncorrectDataNotFinishFile(): void
    {
        $this->countryByBin
            ->expects($this->exactly(2))
            ->method('getCountry')
            ->withConsecutive(
                [
                    "45717360"
                ],
                [
                    "516793"
                ],
                [
                    "45417360"
                ],
                [
                    "41417360"
                ],
                [
                    "4745030"
                ],
            )
            ->willReturnOnConsecutiveCalls(
                "DK",
                "LT",
                "JP",
                "US",
                "GB",
            );

        $this->rate
            ->expects($this->once())
            ->method('getRates')
            ->willReturn(
                [
                    "USD" => 1.111049,
                    "JPY" => 159.384419,
                    "GBP" => 0.842948,
                ]
            );

        $this->log->expects($this->once())->method('info');
        $this->log->expects($this->once())->method('critical')
            ->with('File with wrong format!');
        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/inputIncorrect3Test.txt']);

        $this->assertSame($execute, Command::FAILURE);
    }

    public function testNotExistFile(): void
    {
        $this->countryByBin
            ->expects($this->never())
            ->method('getCountry');

        $this->rate
            ->expects($this->never())
            ->method('getRates');

        $this->log->expects($this->once())->method('info');
        $this->log->expects($this->once())->method('critical')
            ->with('Path or File /var/www/symfony_rate/src/Command/../../tests/Command/Files/NotExist.txt not exist');
        $execute = $this->commandTester->execute(['file' => '../../tests/Command/Files/NotExist.txt']);

        $this->assertSame($execute, Command::FAILURE);
    }
}
