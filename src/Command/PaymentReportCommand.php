<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Service\Twig;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'payment:report')]
class PaymentReportCommand extends Command
{
    private Twig $twig;
    private TransactionRepository $transactionRepository;
    private MailerInterface $mailer;

    public function __construct(
        Twig $twig,
        TransactionRepository $transactionRepository,
        MailerInterface $mailer,
        string $name = null
    ) {
        $this->twig = $twig;
        $this->transactionRepository = $transactionRepository;
        $this->mailer = $mailer;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $endDate = new DateTimeImmutable();
        $startDate = $endDate->modify('-1 month');

        $transactions = $this->transactionRepository->findTransactionsInLastMonth($startDate, $endDate);

        if (count($transactions) > 0) {
            $total = 0;
            foreach ($transactions as $transaction) {
                $total += $transaction['total'];
            }

            $report = $this->twig->render(
                'email/month-report.html.twig',
                [
                    'transactions' => $transactions,
                    'date' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ],
                    'total' => $total
                ]
            );

            try {
                $email = (new Email())
                    ->to(new Address($_ENV['REPORT_EMAIL']))
                    ->from(new Address('admin@study-on.ru'))
                    ->subject('Отчет об оплаченных курсах за период.')
                    ->html($report);

                $this->mailer->send($email);

                $output->writeln('Отчет успешно отправлен!');
                return Command::SUCCESS;
            } catch (TransportExceptionInterface $e) {
                $output->writeln($e->getMessage());
                $output->writeln('Ошибка при формировании и отправке отчета.');

                return Command::FAILURE;
            }
        }
    }
}
