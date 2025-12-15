<?php
declare(strict_types=1);

namespace GardenLawn\Company\Console\Command;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem\DirectoryList;
use GardenLawn\Company\Model\CompanyFactory;
use GardenLawn\Company\Model\ResourceModel\Company as CompanyResource;

class ImportDealers extends Command
{
    private const string STIHL_FILE = 'stihl.json';
    private const string HUSQVARNA_FILE = 'husqvarna.json';
    private const string CONFIG_PATH = 'app/code/GardenLawn/Core/Configs/';

    private State $state;
    private DirectoryList $dir;
    private CompanyFactory $companyFactory;
    private CompanyResource $companyResource;

    public function __construct(
        State $state,
        DirectoryList $dir,
        CompanyFactory $companyFactory,
        CompanyResource $companyResource
    ) {
        $this->state = $state;
        $this->dir = $dir;
        $this->companyFactory = $companyFactory;
        $this->companyResource = $companyResource;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('gardenlawn:import:dealers');
        $this->setDescription('Import dealers from JSON files.');
        parent::configure();
    }

    /**
     * @throws AlreadyExistsException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (Exception $e) {
            // Area code already set
        }

        $this->importStihl($output);
        $this->importHusqvarna($output);

        return 0;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function importStihl(OutputInterface $output): void
    {
        $filePath = $this->dir->getRoot() . '/' . self::CONFIG_PATH . self::STIHL_FILE;
        if (!file_exists($filePath)) {
            $output->writeln('<error>Stihl JSON file not found.</error>');
            return;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        foreach ($data['dealers'] as $dealerData) {
            $company = $this->companyFactory->create();
            $company->setData([
                'customer_group_id' => 5,
                'nip' => $dealerData['accountNumber'],
                'name' => $dealerData['name'],
                'phone' => $dealerData['businessPhone'],
                'email' => $dealerData['email'],
                'www' => $dealerData['website'],
                'address' => $dealerData['street'] . ', ' . $dealerData['zip'] . ' ' . $dealerData['city'],
                'distance' => $dealerData['distance'],
                'status' => 1
            ]);
            $this->companyResource->save($company);
        }

        $output->writeln('<info>Stihl dealers imported successfully.</info>');
    }

    /**
     * @throws AlreadyExistsException
     */
    private function importHusqvarna(OutputInterface $output): void
    {
        $filePath = $this->dir->getRoot() . '/' . self::CONFIG_PATH . self::HUSQVARNA_FILE;
        if (!file_exists($filePath)) {
            $output->writeln('<error>Husqvarna JSON file not found.</error>');
            return;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        foreach ($data['dealers'] as $dealerData) {
            $company = $this->companyFactory->create();
            $company->setData([
                'customer_group_id' => 6,
                'name' => $dealerData['title'],
                'phone' => $dealerData['phone'],
                'email' => $dealerData['email'],
                'www' => $dealerData['web'],
                'address' => str_replace(["\r\n", "\r"], ' ', $dealerData['address']),
                'status' => 1
            ]);
            $this->companyResource->save($company);
        }

        $output->writeln('<info>Husqvarna dealers imported successfully.</info>');
    }
}
