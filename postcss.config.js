/**
 * PostCSS runs inside Vite for `npm run dev` and `npm run build`.
 *
 * Autoprefixer reads the `browserslist` block in package.json, so the same
 * targets cover Chrome, Edge, Firefox, Safari (macOS + iOS) and Samsung
 * Internet. No CSS feature is used without a vendor fallback.
 */
import autoprefixer from 'autoprefixer';

export default {
    plugins: [
        autoprefixer(),
    ],
};
