<?php declare(strict_types=1);

namespace Pim\Upgrade\Schema;

use Akeneo\Connectivity\Connection\Application\Settings\Command\CreateConnectionCommand;
use Akeneo\Connectivity\Connection\Domain\Settings\Exception\ConstraintViolationListException;
use Akeneo\Connectivity\Connection\Domain\Settings\Model\Read\ConnectionWithCredentials;
use Akeneo\Connectivity\Connection\Domain\Settings\Model\ValueObject\FlowType;
use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version_4_0_20191031124707_update_from_clients_to_connections
    extends AbstractMigration
    implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var string[] */
    private $connections = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('SELECT "disable migration warning"');

        $selectClients = <<< SQL
    SELECT id, label FROM pim_api_client;
SQL;
        $clientsStatement = $this->dbalConnection()->executeQuery($selectClients);
        $clients = $clientsStatement->fetchAll();

        $this->skipIf(empty($clients), 'No API connection to migrate.');
        $this->write(sprintf('%s API connections found. They will be migrate to Connection.', count($clients)));

        foreach ($clients as $index => $client) {
            $connectionCode = preg_replace('/[^A-Za-z0-9\-]/', '_', $client['label']); // slugify code
            $connectionCode = substr(str_pad($connectionCode, 3, '_'), 0, 97); // 3 to 100 chars length + unique
            if (isset($this->connections[$connectionCode])) {
                $connectionCode = sprintf('%s_%s', $connectionCode, rand(0, 99)); // make it unique
            }

            $connectionLabel = substr(str_pad($client['label'], 3, '_'), 0, 97); // 3 to 100 chars length

            $connection = $this->createConnection($connectionCode, $connectionLabel);
            $this->connections[$connection->code()] = $connection;

            $this->updateConnectionWithOldClient($connectionCode, $client['id']);

            $this->deleteAutoGeneratedClient($connection->clientId());

            $this->write(sprintf('Client "%s" migrated!', $client['label']));
        }

        $this->write(sprintf('%s Connections created.', count($this->connections)));
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }

    /**
     * Connection has been created with an auto generated client.
     * We update it with the old client id to ensure API clients will continue to work.
     *
     * @param string $code
     * @param string $clientId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateConnectionWithOldClient(string $code, string $clientId): void
    {
        $updateConnectionQuery = <<< SQL
UPDATE akeneo_connectivity_connection
SET client_id = :client_id
WHERE code = :code;
SQL;
        $this->dbalConnection()->executeQuery(
            $updateConnectionQuery,
            [
                'code' => $code,
                'client_id' => $clientId,
            ]
        );
    }

    /**
     * Once the Connection is updated with old client id we remove the auto generated client.
     *
     * @param string $clientId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteAutoGeneratedClient(string $clientId): void
    {
        $deleteClientQuery = <<< SQL
DELETE from pim_api_client WHERE id = :client_id;
SQL;
        $this->dbalConnection()->executeQuery($deleteClientQuery, ['client_id' => $clientId]);
    }

    private function dbalConnection(): DbalConnection
    {
        return $this->container->get('database_connection');
    }

    /**
     * @param string $connectionCode
     * @param string $connectionLabel
     *
     * @return ConnectionWithCredentials
     */
    private function createConnection(string $connectionCode, string $connectionLabel): ConnectionWithCredentials
    {
        $command = new CreateConnectionCommand(
            $connectionCode,
            $connectionLabel,
            FlowType::OTHER
        );

        try {
            return $this->container
                ->get('akeneo_connectivity.connection.application.handler.create_connection')
                ->handle($command);
        } catch (ConstraintViolationListException $constraintViolationListException) {
            foreach ($constraintViolationListException->getConstraintViolationList() as $constraintViolation) {
                $this->write($constraintViolation->getMessage());
            }
            throw $constraintViolationListException;
        }
    }
}
