<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Cocur\BackgroundProcess\BackgroundProcess;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class RetardFunction
{
    public function __construct(
        private UserRepository $userRepository,
        private KernelInterface $appKernel
    ) {
        $this->userRepository = $userRepository;
        $this->appKernel = $appKernel;
    }

    public function DeleteNonValidatedUser(string $email)
    {
        sleep(900);
        $user = $this->userRepository->findOneByEmail($email);
        if ($user) {
            if (!$user->isEmailVerified()) {
                $this->userRepository->remove($user, true);
            }
        }
    }

    public function RunDeleteNonValideUser(string $email)
    {
        $process = new Process(['php', 'bin/console', 'app:delete-invalide-user', $email]);
        $process->setWorkingDirectory(getcwd() . "/..//");
        $process->setOptions(['create_new_console' => true]);
        $process->start();
    }

}