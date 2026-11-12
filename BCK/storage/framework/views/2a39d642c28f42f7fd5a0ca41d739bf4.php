<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#F5F6F8">
    <title><?php echo $__env->yieldContent('title', 'Jonathan Housekeeping'); ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('img/favicon-32.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('img/favicon-180.png')); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: {
                    deepNavy: '#0A2239', paper: '#F5F6F8', hk: '#7E5A86', hkSoft: '#EEE6F0',
                    okGreen: '#5E8F68', fdBlue: '#4F86B0', slateBlue: '#6B7A8C', danger: '#B5563C',
                }
            } }
        }
    </script>
</head>
<body class="bg-paper font-sans antialiased text-deepNavy">
    <div class="max-w-md mx-auto min-h-screen flex flex-col pb-[env(safe-area-inset-bottom,0px)]">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\jonathan\resources\views/layouts/mobile.blade.php ENDPATH**/ ?>