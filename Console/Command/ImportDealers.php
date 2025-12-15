<?php
declare(strict_types=1);

namespace GardenLawn\Company\Console\Command;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use GardenLawn\Company\Model\CompanyFactory;
use GardenLawn\Company\Model\ResourceModel\Company as CompanyResource;
use GardenLawn\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;

class ImportDealers extends Command
{
    private const string STIHL_FILE = 'stihl.json';
    private const string HUSQVARNA_FILE = 'husqvarna.json';

    private State $state;
    private CompanyFactory $companyFactory;
    private CompanyResource $companyResource;
    private CompanyCollectionFactory $companyCollectionFactory;
    private ComponentRegistrarInterface $componentRegistrar;

    public function __construct(
        State $state,
        CompanyFactory $companyFactory,
        CompanyResource $companyResource,
        CompanyCollectionFactory $companyCollectionFactory,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->state = $state;
        $this->companyFactory = $companyFactory;
        $this->companyResource = $companyResource;
        $this->companyCollectionFactory = $companyCollectionFactory;
        $this->componentRegistrar = $componentRegistrar;
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
            $output->writeln('<comment>Area code already set.</comment>');
        }

        $this->importStihl($output);
        $this->importHusqvarna($output);

        return 0;
    }

    private function getFilePath(string $fileName): string
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'GardenLawn_Core');
        return $modulePath . '/Configs/' . $fileName;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function importStihl(OutputInterface $output): void
    {
        $output->writeln('<info>--- Starting Stihl Import ---</info>');
        $filePath = $this->getFilePath(self::STIHL_FILE);
        if (!file_exists($filePath)) {
            $output->writeln('<error>Stihl JSON file not found at: ' . $filePath . '</error>');
            return;
        }
        $output->writeln('<comment>Found file: ' . $filePath . '</comment>');

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln('<error>Error parsing Stihl JSON: ' . json_last_error_msg() . '</error>');
            return;
        }

        if (!isset($data['dealers']) || !is_array($data['dealers'])) {
            $output->writeln('<error>Invalid Stihl JSON format. Missing "dealers" array.</error>');
            return;
        }

        $dealers = $data['dealers'];
        $output->writeln('<comment>Found ' . count($dealers) . ' records in Stihl file.</comment>');

        foreach ($dealers as $dealerData) {
            $dealerName = $dealerData['name'] ?? 'N/A';
            $output->writeln('Processing Stihl dealer: ' . $dealerName);

            $collection = $this->companyCollectionFactory->create();
            $collection->addFieldToFilter('name', $dealerName)
                ->addFieldToFilter('customer_group_id', 5)
                ->setPageSize(1);

            $company = $collection->getFirstItem();

            $action = 'Creating new record...';
            if ($company->getId()) {
                $action = 'Updating existing record (ID: ' . $company->getId() . ')...';
            }
            $output->writeln('  -> ' . $action);

            $company->setData([
                'company_id' => $company->getId(), // Preserve ID if exists
                'customer_group_id' => 5,
                'name' => $dealerName,
                'phone' => $dealerData['businessPhone'],
                'email' => $dealerData['email'],
                'www' => $dealerData['website'],
                'address' => ($dealerData['street'] ?? '') . ', ' . ($dealerData['zip'] ?? '') . ' ' . ($dealerData['city'] ?? ''),
                'distance' => $dealerData['distance'] ?? null,
                'status' => 1
            ]);
            $this->companyResource->save($company);
        }

        $output->writeln('<info>Stihl dealers import finished.</info>');
    }

    /**
     * @throws AlreadyExistsException
     */
    private function importHusqvarna(OutputInterface $output): void
    {
        $output->writeln('<info>--- Starting Husqvarna Import ---</info>');
        $filePath = $this->getFilePath(self::HUSQVARNA_FILE);
        if (!file_exists($filePath)) {
            $output->writeln('<error>Husqvarna JSON file not found at: ' . $filePath . '</error>');
            return;
        }
        $output->writeln('<comment>Found file: ' . $filePath . '</comment>');

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln('<error>Error parsing Husqvarna JSON: ' . json_last_error_msg() . '</error>');
            return;
        }

        if (!isset($data['dealers']) || !is_array($data['dealers'])) {
            $output->writeln('<error>Invalid Husqvarna JSON format. Missing "dealers" array.</error>');
            return;
        }

        $dealers = $data['dealers'];
        $output->writeln('<comment>Found ' . count($dealers) . ' records in Husqvarna file.</comment>');

        foreach ($dealers as $dealerData) {
            $dealerName = $dealerData['title'] ?? 'N/A';
            $output->writeln('Processing Husqvarna dealer: ' . $dealerName);

            $collection = $this->companyCollectionFactory->create();
            $collection->addFieldToFilter('name', $dealerName)
                ->addFieldToFilter('customer_group_id', 6)
                ->setPageSize(1);

            $company = $collection->getFirstItem();

            $action = 'Creating new record...';
            if ($company->getId()) {
                $action = 'Updating existing record (ID: ' . $company->getId() . ')...';
            }
            $output->writeln('  -> ' . $action);

            $company->setData([
                'company_id' => $company->getId(), // Preserve ID if exists
                'customer_group_id' => 6,
                'name' => $dealerName,
                'phone' => $dealerData['phone'],
                'email' => $dealerData['email'],
                'www' => $dealerData['web'],
                'address' => str_replace(["\r\n", "\r"], ' ', $dealerData['address'] ?? ''),
                'status' => 1
            ]);
            $this->companyResource->save($company);
        }

        $output->writeln('<info>Husqvarna dealers import finished.</info>');
    }
}
