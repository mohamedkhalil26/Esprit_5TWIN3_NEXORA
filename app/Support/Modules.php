<?php

namespace App\Support;

/**
 * Registre des modules de l'équipe.
 *
 * Chaque module place ses vues dans resources/views/modules/{module}/.
 * S'il fournit un fichier hooks/{hook}.blade.php, celui-ci est inclus
 * automatiquement par les layouts (menu, tableau de bord, accueil...).
 */
class Modules
{
    /**
     * Ordre d'affichage des modules dans les menus.
     */
    public const ORDRE = ['equipements', 'locations', 'reclamations', 'evenements', 'blog'];

    /**
     * Noms des vues à inclure pour un hook donné.
     *
     * Hooks disponibles : admin-menu, front-menu, dashboard, home, user-menu
     *
     * @return list<string>
     */
    public static function hooks(string $hook): array
    {
        $modules = collect(glob(resource_path('views/modules/*'), GLOB_ONLYDIR))
            ->map(fn ($dossier) => basename($dossier))
            ->sortBy(function ($module) {
                $position = array_search($module, self::ORDRE, true);

                return $position === false ? PHP_INT_MAX : $position;
            });

        return $modules
            ->filter(fn ($module) => file_exists(resource_path("views/modules/{$module}/hooks/{$hook}.blade.php")))
            ->map(fn ($module) => "modules.{$module}.hooks.{$hook}")
            ->values()
            ->all();
    }
}
