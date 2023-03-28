<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\Twig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'payment:ending:notification')]
class PaymentEndingRentCommand extends Command
{
    private Twig $twig;
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;

    private MailerInterface $mailer;

    public function __construct(
        Twig $twig,
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        MailerInterface $mailer,
        string $name = null
    ) {
        $this->twig = $twig;
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $expiresCourse = $this->transactionRepository->findExpiredTransactions();

            if (count($expiresCourse) > 0) {
                $report = $this->twig->render(
                    'email/expires-report.html.twig',
                    [
                        'transactions' => $expiresCourse,
                    ]
                );

                try {
                    $email = (new Email())
                        ->to(new Address($user->getEmail()))
                        ->from(new Address('admin@study-on.ru'))
                        ->subject('Окончание срока аренды.')
                        ->html($report);

                    $this->mailer->send($email);

                    $output->writeln('Отчет успешно отправлен пользователю ' . $user->getEmail() . '!');
                } catch (TransportExceptionInterface $e) {
                    $output->writeln($e->getMessage());
                    $output->writeln(
                        'Ошибка при формировании и отправке отчета пользователю ' . $user->getEmail() . '.'
                    );

                    return Command::FAILURE;
                }
            }
        }
        $output->writeln('Отчеты успешно отправлены!');
        return Command::SUCCESS;
    }
}