<?php

namespace App\Providers;

use App\Observers\StorefrontMediaObserver;
use Carbon\CarbonImmutable;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Forms\View\FormsIconAlias;
use Filament\Notifications\View\NotificationsIconAlias;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\View\SupportIconAlias;
use Filament\Tables\View\TablesIconAlias;
use Filament\View\PanelsIconAlias;
use Filament\Widgets\View\WidgetsIconAlias;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Media::observe(StorefrontMediaObserver::class);

        $this->configureFilamentIcons();
        $this->configureDefaults();
    }

    private function configureFilamentIcons(): void
    {
        FilamentIcon::register([
            PanelsIconAlias::GLOBAL_SEARCH_FIELD => 'phosphor-magnifying-glass',
            PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM => 'phosphor-squares-four',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'phosphor-sidebar-simple',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => 'phosphor-sidebar-simple',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'phosphor-sidebar-simple',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => 'phosphor-sidebar-simple',
            PanelsIconAlias::SIDEBAR_GROUP_COLLAPSE_BUTTON => 'phosphor-caret-up',
            PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON => 'phosphor-sun',
            PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON => 'phosphor-moon',
            PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON => 'phosphor-desktop',
            PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON => 'phosphor-x',
            PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON => 'phosphor-list',
            PanelsIconAlias::USER_MENU_PROFILE_ITEM => 'phosphor-user-circle',
            PanelsIconAlias::USER_MENU_LOGOUT_BUTTON => 'phosphor-sign-out',
            PanelsIconAlias::USER_MENU_TOGGLE_BUTTON => 'phosphor-user-circle',
            ActionsIconAlias::ACTION_GROUP => 'phosphor-dots-three',
            ActionsIconAlias::CREATE_ACTION_GROUPED => 'phosphor-plus',
            ActionsIconAlias::DELETE_ACTION => 'phosphor-trash',
            ActionsIconAlias::DELETE_ACTION_GROUPED => 'phosphor-trash',
            ActionsIconAlias::DELETE_ACTION_MODAL => 'phosphor-trash',
            ActionsIconAlias::EDIT_ACTION => 'phosphor-pencil-simple',
            ActionsIconAlias::EDIT_ACTION_GROUPED => 'phosphor-pencil-simple',
            ActionsIconAlias::IMPORT_ACTION_GROUPED => 'phosphor-upload-simple',
            ActionsIconAlias::MODAL_CONFIRMATION => 'phosphor-warning-circle',
            ActionsIconAlias::RESTORE_ACTION => 'phosphor-arrow-counter-clockwise',
            ActionsIconAlias::RESTORE_ACTION_GROUPED => 'phosphor-arrow-counter-clockwise',
            ActionsIconAlias::RESTORE_ACTION_MODAL => 'phosphor-arrow-counter-clockwise',
            ActionsIconAlias::VIEW_ACTION => 'phosphor-eye',
            ActionsIconAlias::VIEW_ACTION_GROUPED => 'phosphor-eye',
            FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD => 'phosphor-magnifying-glass',
            FormsIconAlias::COMPONENTS_SELECT_ACTIONS_CREATE_OPTION => 'phosphor-plus',
            FormsIconAlias::COMPONENTS_SELECT_ACTIONS_EDIT_OPTION => 'phosphor-pencil-simple',
            FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_COPY => 'phosphor-copy',
            FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD => 'phosphor-eye-closed',
            FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD => 'phosphor-eye',
            FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE => 'phosphor-x',
            FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE => 'phosphor-check',
            NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE => 'phosphor-bell',
            NotificationsIconAlias::NOTIFICATION_CLOSE_BUTTON => 'phosphor-x',
            NotificationsIconAlias::NOTIFICATION_DANGER => 'phosphor-x-circle',
            NotificationsIconAlias::NOTIFICATION_INFO => 'phosphor-info',
            NotificationsIconAlias::NOTIFICATION_SUCCESS => 'phosphor-check-circle',
            NotificationsIconAlias::NOTIFICATION_WARNING => 'phosphor-warning',
            TablesIconAlias::ACTIONS_FILTER => 'phosphor-funnel',
            TablesIconAlias::ACTIONS_GROUP => 'phosphor-dots-three',
            TablesIconAlias::ACTIONS_COLUMN_MANAGER => 'phosphor-columns',
            TablesIconAlias::COLUMNS_ICON_COLUMN_FALSE => 'phosphor-x-circle',
            TablesIconAlias::COLUMNS_ICON_COLUMN_TRUE => 'phosphor-check-circle',
            TablesIconAlias::EMPTY_STATE => 'phosphor-package',
            TablesIconAlias::HEADER_CELL_SORT_ASC_BUTTON => 'phosphor-caret-up',
            TablesIconAlias::HEADER_CELL_SORT_BUTTON => 'phosphor-caret-up-down',
            TablesIconAlias::HEADER_CELL_SORT_DESC_BUTTON => 'phosphor-caret-down',
            TablesIconAlias::SEARCH_FIELD => 'phosphor-magnifying-glass',
            SupportIconAlias::BADGE_DELETE_BUTTON => 'phosphor-x',
            SupportIconAlias::BREADCRUMBS_SEPARATOR => 'phosphor-caret-right',
            SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL => 'phosphor-caret-left',
            SupportIconAlias::MODAL_CLOSE_BUTTON => 'phosphor-x',
            SupportIconAlias::PAGINATION_NEXT_BUTTON => 'phosphor-caret-right',
            SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL => 'phosphor-caret-left',
            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON => 'phosphor-caret-left',
            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL => 'phosphor-caret-right',
            SupportIconAlias::SECTION_COLLAPSE_BUTTON => 'phosphor-caret-up',
            WidgetsIconAlias::CHART_WIDGET_FILTER => 'phosphor-funnel',
            WidgetsIconAlias::CHART_WIDGET_EMPTY_STATE => 'phosphor-chart-bar',
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
