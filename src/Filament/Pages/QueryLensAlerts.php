<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Pages;

use GladeHQ\QueryLens\Filament\Concerns\BasePageResolver;
use GladeHQ\QueryLens\Models\Alert;

/**
 * Alerts page for the Filament plugin.
 *
 * When Filament is installed, this extends Filament\Pages\Page with HasTable
 * integration, providing CRUD actions for alert management via modals.
 * When Filament is absent, it serves as a standalone data assembler.
 */
class QueryLensAlerts extends BasePageResolver
{
    protected static string $navigationIcon = 'heroicon-o-bell-alert';
    protected static string $navigationGroup = 'Query Lens';
    protected static ?string $title = 'Query Alerts';
    protected static ?string $slug = 'query-lens/alerts';
    protected static int $navigationSort = 3;
    protected static string $view = 'query-lens::filament.alerts';
    protected static ?string $navigationLabel = 'Query Alerts';

    /**
     * Define table columns for the alerts list.
     *
     * When Filament is installed, these map to real Table Builder columns:
     * TextColumn, BadgeColumn, ToggleColumn, etc.
     */
    public static function getTableColumnDefinitions(): array
    {
        return [
            [
                'name' => 'name',
                'label' => 'Name',
                'searchable' => true,
                'type' => 'text',
            ],
            [
                'name' => 'type',
                'label' => 'Type',
                'type' => 'badge',
                'filterable' => true,
            ],
            [
                'name' => 'conditions',
                'label' => 'Threshold',
                'type' => 'text',
                'description' => 'Extracted from conditions array',
            ],
            [
                'name' => 'channels',
                'label' => 'Channels',
                'type' => 'badge_list',
            ],
            [
                'name' => 'enabled',
                'label' => 'Enabled',
                'type' => 'toggle',
            ],
            [
                'name' => 'created_at',
                'label' => 'Created At',
                'sortable' => true,
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Define table filters for alerts.
     */
    public static function getTableFilterDefinitions(): array
    {
        return [
            [
                'name' => 'type',
                'label' => 'Alert Type',
                'type' => 'select',
                'options' => array_keys(Alert::getAvailableTypes()),
            ],
            [
                'name' => 'enabled',
                'label' => 'Status',
                'type' => 'ternary',
            ],
        ];
    }

    /**
     * Define table actions for each alert row.
     *
     * When Filament is installed, these map to Filament Action objects:
     * EditAction (modal form), toggle Action, DeleteAction (with confirmation).
     */
    public static function getTableActionDefinitions(): array
    {
        return [
            [
                'name' => 'edit',
                'label' => 'Edit',
                'icon' => 'heroicon-o-pencil-square',
                'type' => 'modal',
            ],
            [
                'name' => 'toggle',
                'label' => 'Toggle',
                'icon' => 'heroicon-o-power',
                'type' => 'action',
            ],
            [
                'name' => 'delete',
                'label' => 'Delete',
                'icon' => 'heroicon-o-trash',
                'type' => 'action',
                'requiresConfirmation' => true,
                'color' => 'danger',
            ],
        ];
    }

    /**
     * Define header actions for the alerts page.
     *
     * When Filament is installed, the Create action opens a modal form
     * with the schema defined in getAlertFormDefinitions().
     */
    public static function getHeaderActionDefinitions(): array
    {
        return [
            [
                'name' => 'create',
                'label' => 'Create Alert',
                'icon' => 'heroicon-o-plus',
                'type' => 'modal',
            ],
        ];
    }

    /**
     * Define the form schema for creating/editing alerts.
     *
     * When Filament is installed, these map to real Filament Form Builder
     * components: TextInput, Select, KeyValue, CheckboxList, Toggle, etc.
     */
    public static function getAlertFormDefinitions(): array
    {
        return [
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'required' => true,
                'maxLength' => 255,
            ],
            [
                'name' => 'type',
                'label' => 'Type',
                'type' => 'select',
                'required' => true,
                'options' => Alert::getAvailableTypes(),
            ],
            [
                'name' => 'conditions',
                'label' => 'Conditions',
                'type' => 'key_value',
                'description' => 'Alert trigger conditions',
            ],
            [
                'name' => 'channels',
                'label' => 'Channels',
                'type' => 'checkbox_list',
                'required' => true,
                'options' => Alert::getAvailableChannels(),
            ],
            [
                'name' => 'cooldown_minutes',
                'label' => 'Cooldown (minutes)',
                'type' => 'numeric',
                'default' => 5,
                'min' => 1,
            ],
            [
                'name' => 'enabled',
                'label' => 'Enabled',
                'type' => 'toggle',
                'default' => true,
            ],
        ];
    }

    /**
     * Assemble view data for the alerts page.
     */
    public function getViewData(): array
    {
        return [
            'alerts' => $this->loadAlerts(),
            'availableTypes' => Alert::getAvailableTypes(),
            'availableChannels' => Alert::getAvailableChannels(),
        ];
    }

    public function loadAlerts(): array
    {
        try {
            return Alert::orderByDesc('created_at')->get()->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    public function toggleAlert(int $alertId): bool
    {
        $alert = Alert::find($alertId);
        if (! $alert) {
            return false;
        }

        $alert->update(['enabled' => ! $alert->enabled]);

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? 'Query Alerts';
    }
}
