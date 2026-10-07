<?php
$content = file_get_contents('resources/views/layouts/navigation.blade.php');

// Replace left/logo container
$content = str_replace('<div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center transform hover:scale-105 transition-transform duration-300 mr-4 xl:mr-10">
                    <a href="{{ url(\'/\') }}" class="flex items-center gap-3 xl:gap-4">', 
'<div class="flex items-center shrink-0">
                <!-- Logo -->
                <div class="flex items-center transform hover:scale-105 transition-transform duration-300 mr-4 lg:mr-6">
                    <a href="{{ url(\'/\') }}" class="flex items-center gap-2 lg:gap-3">', $content);

// Change text size in logo
$content = str_replace('<span class="text-[16px] xl:text-[18px]', '<span class="text-[14px] lg:text-[16px]', $content);
$content = str_replace('<span class="text-[9px]', '<span class="text-[8px]', $content);

// Navigation links container
$content = str_replace('<div class="hidden xl:flex space-x-6 xl:space-x-8 h-full items-center ml-4">', 
'<div class="hidden lg:flex flex-1 justify-center space-x-4 lg:space-x-6 h-full items-center px-2">', $content);

// Link classes
$content = str_replace('$linkClasses = "inline-flex items-center text-[13px] xl:text-[14px] font-bold transition-all duration-300 relative whitespace-nowrap";', 
'$linkClasses = "inline-flex items-center text-[12px] lg:text-[13px] font-bold transition-all duration-300 relative whitespace-nowrap";', $content);

// Right side container
$content = str_replace('<div class="hidden xl:flex xl:items-center gap-4 xl:gap-6">', 
'<div class="hidden lg:flex lg:items-center gap-3 lg:gap-4 shrink-0">', $content);

file_put_contents('resources/views/layouts/navigation.blade.php', $content);
echo "Patched navigation!";
