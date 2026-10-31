<?php

// Local Windows development stays on Laravel's public disk.
// Render uses Cloudinary when AFTERGLOW_IMAGE_STORAGE=cloudinary is configured.
return [
    'driver' => env('AFTERGLOW_IMAGE_STORAGE', 'public'),
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
];
