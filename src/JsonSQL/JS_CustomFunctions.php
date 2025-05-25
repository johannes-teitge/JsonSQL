<?php
namespace Src\JsonSQL;


trait JS_CustomFunctions
{

    // Array, um benutzerdefinierte Funktionen zu speichern
    protected static array $customFunctions = [];

    // Methode zum Hinzufügen benutzerdefinierter Funktionen
    public static function addCustomFunction(string $functionName, callable $function): void {
        self::$customFunctions[$functionName] = $function;
    }

    // Methode zum Aufrufen von benutzerdefinierten Funktionen
    public static function callCustomFunction(string $functionName, ...$args) {
        if (isset(self::$customFunctions[$functionName])) {
            return call_user_func_array(self::$customFunctions[$functionName], $args);
        }
        throw new \Exception("Funktion '$functionName' nicht gefunden.");
    }

    // Methode zum Abrufen aller benutzerdefinierten Funktionen
    public static function getCustomFunctions(): array {
        return self::$customFunctions;
    }

}    
