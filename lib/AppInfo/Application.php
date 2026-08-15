<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\AppInfo;

use OCP\AppFramework\App;

final class Application extends App {
    public const APP_ID = 'adbqplanung';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }
}
