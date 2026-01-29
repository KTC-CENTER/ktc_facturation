const Encore = require('@symfony/webpack-encore');

// Configuration manuelle de l'environnement runtime si non défini
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Répertoire où les assets compilés seront stockés
    .setOutputPath('public/build/')
    // Chemin public utilisé par le serveur web pour accéder aux assets
    .setPublicPath('/build')

    /*
     * ENTRÉES
     * Chaque entrée résultera en un fichier JavaScript et un fichier CSS
     */
    .addEntry('app', './assets/js/app.js')
    .addEntry('dashboard', './assets/js/dashboard.js')

    // Entrée CSS séparée pour les styles globaux
    .addStyleEntry('styles', './assets/css/app.css')

    // Active le système de versionnage des fichiers
    .enableVersioning(Encore.isProduction())

    // Active la génération des sourcemaps
    .enableSourceMaps(!Encore.isProduction())

    // Active la création d'un fichier runtime.js séparé
    .enableSingleRuntimeChunk()

    /*
     * CONFIGURATION
     */
    // Active le support PostCSS (inclut Tailwind CSS)
    .enablePostCssLoader()

    // Active Stimulus pour les contrôleurs JavaScript
    .enableStimulusBridge('./assets/controllers.json')

    // Active l'intégration avec Hotwired Turbo
    .splitEntryChunks()

    // Active le hashing des noms de fichiers en production
    .cleanupOutputBeforeBuild()

    // Active les notifications de build
    .enableBuildNotifications()

    // Désactive l'intégrité des sous-ressources (SRI)
    .enableIntegrityHashes(Encore.isProduction())

    // Configure Babel
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // Configure les options de Babel pour les dépendances
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.33';
    })

    // Configure l'extraction du CSS
    .configureCssLoader((config) => {
        config.modules = false;
    })

    // Copie les images et les polices
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
    })
;

module.exports = Encore.getWebpackConfig();
