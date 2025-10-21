<?php
// Google Maps configuration
// IMPORTANT: replace 'YOUR_API_KEY_HERE' with your actual API key or set the environment variable GOOGLE_PLACES_API_KEY
return [
    // IMPORTANT: this key was set by the developer via the tool. Keep this file secure and don't commit to public repos.
    'places_api_key' => getenv('GOOGLE_PLACES_API_KEY') ?: 'GOOGLE_API_KEY_PLACEHOLDER',
];
