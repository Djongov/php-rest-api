<?php declare(strict_types=1);

namespace App\Markdown;

use Components\Alerts;
use App\Utilities\Parsers;
use Parsedown;

class Page
{
    public static function render(string $fileName, string $theme) : string
    {
        // Now let's get the contents of the file
        if (file_exists($fileName . '.md') === false) {
            http_response_code(404);
            return Alerts::danger('page does not exist');
        }
        $fileContents = file_get_contents($fileName . '.md');
        if (str_starts_with($fileContents, '---')) {
            // If metadata exists, split the content into metadata and Markdown content
            list($metadata, $markdown) = explode("\n---\n", $fileContents, 2);
        } else {
            // If no metadata, set metadata to an empty string and use the entire content as Markdown
            $metadata = '';
            $markdown = $fileContents;
        }
        // Now let's render the markdown
        $html = '<article class="mx-6 my-4 max-w-full p-6 ' . LIGHT_COLOR_SCHEME_CLASS . ' ' . DARK_COLOR_SCHEME_CLASS . ' ' . TEXT_COLOR_SCHEME . ' ' . TEXT_DARK_COLOR_SCHEME . ' prose prose-md prose-' . $theme . ' dark:prose-invert overflow-auto">';
            $html .= Parsedown::instance()->text($markdown);
        $html .= '</article>';
        return $html;
    }
    public static function renderRemote(string $url, string $theme) : string
    {
        // Now let's get the contents of the file
        $fileContents = file_get_contents($url);
        if (empty($fileContents)) {
            http_response_code(404);
            return Alerts::danger('Could not fetch remote page');
        }
        if (str_starts_with($fileContents, '---')) {
            // If metadata exists, split the content into metadata and Markdown content
            list($metadata, $markdown) = explode("\n---\n", $fileContents, 2);
        } else {
            // If no metadata, set metadata to an empty string and use the entire content as Markdown
            $metadata = '';
            $markdown = $fileContents;
        }
        // Now let's render the markdown
        $html = '<article class="mx-6 my-4 max-w-full p-6 ' . LIGHT_COLOR_SCHEME_CLASS . ' ' . DARK_COLOR_SCHEME_CLASS . ' ' . TEXT_COLOR_SCHEME . ' ' . TEXT_DARK_COLOR_SCHEME . ' prose prose-md prose-' . $theme . ' dark:prose-invert">';
            $html .= Parsedown::instance()->text($markdown);
        $html .= '</article>';
        return $html;
    }
    public static function getMdFilesInDir(string $dir) : array
    {
        $files = scandir($dir);
        $files = array_diff($files, ['.', '..', 'index.php']);
        $files = array_map(function ($file) {
            return str_replace('.md', '', $file);
        }, $files);
        return $files;
    }
    public static function getMetaDataFromMd($file, $folder) : array
    {
        $yamlData = self::readMetaDataFromMd($file, $folder);

        $title = $yamlData['title'] ?? ucfirst(str_replace('-', ' ', basename($_SERVER['REQUEST_URI'])));
        $description = $yamlData['description'] ?? GENERIC_DESCRIPTION;
        $keywords = (isset($yamlData['keywords'])) ? explode(',', $yamlData['keywords']) : GENERIC_KEYWORDS;
        $thumbimage = $yamlData['thumbimage'] ?? OG_LOGO;
        $menu = $yamlData['menu'] ?? MAIN_MENU; // This has no real effect as of now but allows for passing a MENU
        $genericMetaDataArray = [
            'metadata' => [
                // Title will be the uppercased file name
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords,
                'thumbimage' => $thumbimage,
                'menu' => $menu,
            ]
        ];
        return $genericMetaDataArray;
    }
    public static function readMetaDataFromMd($file, $folder) : array
    {
        $fileContents = file_get_contents($folder . '/' . $file . '.md');
        
        // Find the position of the first occurrence of '---' (start of YAML front matter)
        $frontMatterStart = strpos($fileContents, '---');

        if ($frontMatterStart !== false) {
            // Find the position of the next '---' after the start of YAML front matter
            $frontMatterEnd = strpos($fileContents, '---', $frontMatterStart + 3);

            if ($frontMatterEnd !== false) {
                // Extract the YAML front matter
                $frontMatter = substr($fileContents, $frontMatterStart + 3, $frontMatterEnd - $frontMatterStart - 3);

                // Parse YAML front matter into an associative array manually
                $yamlData = Parsers::yaml($frontMatter);

                // Extract the content after the YAML front matter
                $markdownContent = substr($fileContents, $frontMatterEnd + 3);

                return $yamlData;
            }
        }
        return [];
    }
}
