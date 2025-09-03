<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of ClearDatabseCommand
 *
 * @author Dev
 */
class ClearDatabaseCommand extends Command
{
    protected static $defaultName = 'app:clear-database';

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }
    
     protected function configure(): void
    {
        // Configuration de la commande, par exemple la description
        $this->setDescription('Une commande customizer pour vider tous les tables.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->entityManager->getConnection();
        $platform   = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0'); // Désactiver la vérification des clés étrangères

        $tables = $connection->getSchemaManager()->listTableNames();
        foreach ($tables as $table) {
            $sql = $platform->getTruncateTableSQL($table, true);
            $connection->executeStatement($sql);
        }

        $connection->query('SET FOREIGN_KEY_CHECKS=1'); // Réactiver la vérification des clés étrangères

        $output->writeln('');
        $output->writeln('------------------------------------------');
        $output->writeln('<info>Toutes les tables ont été vidées.</info>');
        $output->writeln('------------------------------------------');

        return Command::SUCCESS;
    }
}
