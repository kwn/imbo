<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Model\Image,
    Imbo\Model\Images,
    Imbo\Resource\Images\Query,
    Imbo\Exception\DatabaseException,
    Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Connection,
    PDO,
    DateTime,
    DateTimeZone;

/**
 * Doctrine 2 database driver for the image variations
 *
 * Valid parameters for this driver:
 *
 * - <pre>(string) dbname</pre> Name of the database to connect to
 * - <pre>(string) user</pre> Username to use when connecting
 * - <pre>(string) password</pre> Password to use when connecting
 * - <pre>(string) host</pre> Hostname to use when connecting
 * - <pre>(string) driver</pre> Which driver to use
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Database
 */
class Doctrine implements DatabaseInterface {
    /**
     * Parameters for the Doctrine connection
     *
     * @var array
     */
    private $params = [
        'dbname'    => null,
        'user'      => null,
        'password'  => null,
        'host'      => null,
        'driver'    => null,
        'tableName' => 'imagevariations',
    ];

    /**
     * Doctrine connection
     *
     * @var Connection
     */
    private $connection;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param Connection $connection Optional connection instance
     */
    public function __construct(array $params = null, Connection $connection = null) {
        if ($params !== null) {
            $this->params = array_merge($this->params, $params);
        }

        if ($connection !== null) {
            $this->setConnection($connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeImageVariationMetadata($publicKey, $imageIdentifier, $width, $height) {
        return (boolean) $this->getConnection()->insert($this->params->tableName, [
            'added'           => time(),
            'publicKey'       => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'width'           => $width,
            'height'          => $height,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBestMatch($publicKey, $imageIdentifier, $width) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('width', 'height')
              ->from($this->params['tableName'], 'iv')
              ->where('iv.publicKey = :publicKey')
              ->andWhere('iv.imageIdentifier = :imageIdentifier')
              ->andWhere('iv.width >= :width')
              ->limit(1)
              ->orderBy('iv.width', 'ASC')
              ->setParameters([
                  ':publicKey'       => $publicKey,
                  ':imageIdentifier' => $imageIdentifier,
                  ':width'           => $width,
              ]);

        $stmt = $qb->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($publicKey, $imageIdentifier, $width = null) {
        $qb = $this->getConnection()->createQueryBuilder();

        $qb->delete($this->params['tableName'])
           ->where('publicKey = :publicKey')
           ->andWhere('imageIdentifier = :imageIdentifier')
           ->setParameters([
               ':publicKey' => $publicKey,
               ':imageIdentifier' => $imageIdentifier,
           ]);

        if ($width) {
            $qb->andWhere('width = :width')
               ->setParameter(':width', $width);
        }

        return (boolean) $qb->execute();
    }

    /**
     * Set the connection instance
     *
     * @param Connection $connection The connection instance
     * @return self
     */
    private function setConnection(Connection $connection) {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the Doctrine connection
     *
     * @return Connection
     */
    private function getConnection() {
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->params, new Configuration());
        }

        return $this->connection;
    }
}
