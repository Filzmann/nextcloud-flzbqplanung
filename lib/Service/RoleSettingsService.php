<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Service;

use DomainException;
use OCA\AdBqPlanning\AppInfo\Application;
use OCP\IAppConfig;
use OCP\IGroupManager;

final class RoleSettingsService {
    public function __construct(private IAppConfig $config, private IGroupManager $groups) {
    }

    /** @return array{planningGroup:string,teachingGroup:string,publishingGroup:string} */
    public function current(): array {
        return [
            'planningGroup' => $this->config->getValueString(Application::APP_ID, 'role_group_planning', ''),
            'teachingGroup' => $this->config->getValueString(Application::APP_ID, 'role_group_teaching', ''),
            'publishingGroup' => $this->config->getValueString(Application::APP_ID, 'role_group_publishing', ''),
        ];
    }

    /** @return array{planningGroup:string,teachingGroup:string,publishingGroup:string} */
    public function update(string $planningGroup, string $teachingGroup, string $publishingGroup): array {
        $values = [
            'planningGroup' => trim($planningGroup),
            'teachingGroup' => trim($teachingGroup),
            'publishingGroup' => trim($publishingGroup),
        ];
        foreach ($values as $groupId) {
            if ($groupId !== '' && $this->groups->get($groupId) === null) {
                throw new DomainException('Eine konfigurierte BQ-Rollengruppe existiert nicht in Nextcloud.');
            }
        }
        $this->config->setValueString(Application::APP_ID, 'role_group_planning', $values['planningGroup']);
        $this->config->setValueString(Application::APP_ID, 'role_group_teaching', $values['teachingGroup']);
        $this->config->setValueString(Application::APP_ID, 'role_group_publishing', $values['publishingGroup']);
        return $this->current();
    }
}
