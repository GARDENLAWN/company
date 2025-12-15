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
            // Area code already set
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
        $filePath = $this->getFilePath(self::STIHL_FILE);
        if (!file_exists($filePath)) {
            $output->writeln('<error>Stihl JSON file not found at: ' . $filePath . '</error>');
            return;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        foreach ($data['dealers'] as $dealerData) {
            $collection = $this->companyCollectionFactory->create();
            $collection->addFieldToFilter('name', $dealerData['name'])
                ->addFieldToFilter('customer_group_id', 5)
                ->setPageSize(1);

            $company = $collection->getFirstItem();

            if (!$company->getId()) {
                $company = $this->companyFactory->create();
            }

            $company->setData([
                'company_id' => $company->getId(), // Preserve ID if exists
                'customer_group_id' => 5,
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
        $filePath = $this->getFilePath(self::HUSQVARNA_FILE);
        if (!file_exists($filePath)) {
            $output->writeln('<error>Husqvarna JSON file not found at: ' . $filePath . '</error>');
            return;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        foreach ($data['dealers'] as $dealerData) {
            $collection = $this->companyCollectionFactory->create();
            $collection->addFieldToFilter('name', $dealerData['title'])
                ->addFieldToFilter('customer_group_id', 6)
                ->setPageSize(1);

            $company = $collection->getFirstItem();

            if (!$company->getId()) {
                $company = $this->companyFactory->create();
            }

            $company->setData([
                'company_id' => $company->getId(), // Preserve ID if exists
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
