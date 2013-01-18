<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Container,
    Imbo\ContainerAware,
    DateTime;

/**
 * Database operations event listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class DatabaseOperations implements ContainerAware, ListenerInterface {
    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('db.image.insert', array($this, 'insertImage')),
            new ListenerDefinition('db.image.delete', array($this, 'deleteImage')),
            new ListenerDefinition('db.image.load', array($this, 'loadImage')),
            new ListenerDefinition('db.images.load', array($this, 'loadImages')),
            new ListenerDefinition('db.metadata.delete', array($this, 'deleteMetadata')),
            new ListenerDefinition('db.metadata.update', array($this, 'updateMetadata')),
            new ListenerDefinition('db.metadata.load', array($this, 'loadMetadata')),
            new ListenerDefinition('db.user.load', array($this, 'loadUser')),
        );
    }

    /**
     * Insert an image
     *
     * @param EventInterface $event An event instance
     */
    public function insertImage(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getDatabase()->insertImage(
            $request->getPublicKey(),
            $request->getImage()->getChecksum(),
            $request->getImage()
        );
    }

    /**
     * Delete an image
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->deleteImage(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Load an image
     *
     * @param EventInterface $event An event instance
     */
    public function loadImage(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getDatabase()->load(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            $response->getImage()
        );
    }

    /**
     * Delete metadata
     *
     * @param EventInterface $event An event instance
     */
    public function deleteMetadata(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->deleteMetadata(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Update metadata
     *
     * @param EventInterface $event An event instance
     */
    public function updateMetadata(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->updateMetadata(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            json_decode($request->getRawData(), true)
        );
    }

    /**
     * Load metadata
     *
     * @param EventInterface $event An event instance
     */
    public function loadMetadata(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $database = $event->getDatabase();

        $response->setBody($database->getMetadata($publicKey, $imageIdentifier));
        $response->getHeaders()->set(
            'Last-Modified',
            $this->formatDate(
                $database->getLastModified($publicKey, $imageIdentifier)
            )
        );
    }

    /**
     * Load images
     *
     * @param EventInterface $event An event instance
     */
    public function loadImages(EventInterface $event) {
        $params = $event->getRequest()->getQuery();
        $query = $this->container->get('imagesQuery');

        if ($params->has('page')) {
            $query->page($params->get('page'));
        }

        if ($params->has('limit')) {
            $query->limit($params->get('limit'));
        }

        if ($params->has('metadata')) {
            $query->returnMetadata($params->get('metadata'));
        }

        if ($params->has('from')) {
            $query->from($params->get('from'));
        }

        if ($params->has('to')) {
            $query->to($params->get('to'));
        }

        if ($params->has('query')) {
            $data = json_decode($params->get('query'), true);

            if (is_array($data)) {
                $query->metadataQuery($data);
            }
        }

        $publicKey = $event->getRequest()->getPublicKey();
        $response = $event->getResponse();
        $database = $event->getDatabase();

        $images = $database->getImages($publicKey, $query);

        foreach ($images as &$image) {
            $image['added'] = $this->formatDate($image['added']);
            $image['updated'] = $this->formatDate($image['updated']);
        }

        $lastModified = $this->formatDate($database->getLastModified($publicKey));

        $response->setBody($images)
                 ->getHeaders()->set('Last-Modified', $lastModified);
    }

    /**
     * Load user data
     *
     * @param EventInterface $event An event instance
     */
    public function loadUser(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $database = $event->getDatabase();

        $numImages = $database->getNumImages($publicKey);
        $lastModified = $this->formatDate($database->getLastModified($publicKey));

        $response->setBody(array(
            'publicKey'    => $publicKey,
            'numImages'    => $numImages,
            'lastModified' => $lastModified,
        ));
        $response->getHeaders()->set('Last-Modified', $lastModified);
    }

    /**
     * Format a DateTime instance
     *
     * @param DateTime $date A DateTime instance
     * @return string A formatted date
     */
    private function formatDate(DateTime $date) {
        return $this->container->get('dateFormatter')->formatDate($date);
    }
}