<?php declare(strict_types=1);

namespace App\Response;

class HttpErrorHandler
{

    public static function render($code, $title, $message, $theme) : string
    {
        $html = '';
        $html .= '<div class="text-black dark:text-white container px-4 mx-auto mb-12">';
            $html .= '<div class="md:max-w-4xl mb-8 mx-auto text-center">';
                $html .= '<span class="inline-block py-px px-2 my-4 text-xs leading-5 text-gray-50 dark:text-white bg-red-500 dark:bg-red-500 font-medium uppercase rounded-full shadow-sm">Error</span>';
                $html .= '<h1 class="mb-4 text-3xl md:text-4xl leading-tight font-bold tracking-tighter">' . $code . ' - ' . $title . '</h1>';
                $html .= '<p class="text-lg md:text-xl text-coolGray-500 font-medium">' . $message . '</p>';
            $html .= '</div>';
            $html .= '<div class="w-full md:w-auto flex items-center">';
                $html .= '<button class="back-button mx-auto py-3 px-5 leading-5 text-white bg-' . $theme . '-500 hover:bg-' . $theme . '-600 font-medium text-center focus:ring-2 focus:ring-' . $theme . '-500 focus:ring-opacity-50 border border-transparent rounded-md shadow-sm">';
                    $html .= 'Go Back';
                $html .= '</button>';
            $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
