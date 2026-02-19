/*
 * KTC Invoice Pro - Bootstrap Stimulus
 */

import { startStimulusApp } from '@symfony/stimulus-bridge';

// Enregistre les contrôleurs Stimulus de l'application
// Ils seront automatiquement chargés depuis le dossier controllers/
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
