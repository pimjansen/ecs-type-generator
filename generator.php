<?php
require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class EcsGenerator
{
    /**
     * @param string $string
     * @return string
     */
    public function stringToHungarian(string $string): string
    {
        $string = str_replace('_', '', ucwords($string, '_'));
        $string = str_replace('.', '', ucwords($string, '.'));
        $string = str_replace(' ', '', $string);
        return $string;
    }

    /**
     * @param string $field
     * @return string
     */
    public function formatInternalField(string $field): string
    {
        $field = str_replace(".", "']['", $field);
        return $field;
    }

    /**
     * @param string $type
     * @return string
     */
    public function elasticTypeToPhp(string $type): string
    {
        switch($type) {
            case 'keyword':
                return 'string';
            case 'number':
            case 'long':
                return 'int';
            default:
                return $type;
        }
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getMethodFieldData(array $fields): array
    {
        $methodCollection = [];
        foreach ($fields as $field => $fieldData) {
            $methodCollection[] = [
                'name' => $this->stringToHungarian($field),
                'internal' => $this->formatInternalField($field),
                'type' => $this->elasticTypeToPhp($fieldData['type']),
                'description' => $fieldData['description'],
                'example' => $fieldData['example'] ?? null,
            ];
        }

        return $methodCollection;
    }

    public function __invoke()
    {
        $loader = new FilesystemLoader('./templates');
        $twig = new Environment($loader, [
            'debug' => true,
        ]);
        $schema = Yaml::parseFile('./schema.yml');
        foreach ($schema as $type => $data) {

            $className = $this->stringToHungarian($data['title']);
            $template = $twig->load('class.twig');

            // Output content
            $renderedClassTemplate = $template->render([
                'className' => $className,
                'version' => 'v1.8',
                'description' => $data['description'],
                'docsUrl' => 'https://www.elastic.co/guide/en/ecs/current/ecs-'.$data['name'].'.html',
                'methodCollection' => $this->getMethodFieldData($data['fields']),
            ]);
            file_put_contents(
                sprintf(
                    './generated/%s.php',
                    $className
                ),
                $renderedClassTemplate
            );
        }
    }
}

$generator = new EcsGenerator();
$generator();
