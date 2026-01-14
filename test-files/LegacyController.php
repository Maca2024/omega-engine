<?php

// LEGACY PHP CODE - Voorbeeld van oude code die gerefactored moet worden

class LegacyController
{
    private $userService;
    private $logger;
    private $config;

    public function __construct($userService, $logger, $config)
    {
        $this->userService = $userService;
        $this->logger = $logger;
        $this->config = $config;
    }

    // Geen return type, geen parameter types
    public function getUser($id)
    {
        if ($id == null) {
            return null;
        }

        $user = $this->userService->find($id);

        if ($user == null) {
            $this->logger->warning("User not found: " . $id);
            return null;
        }

        return $user;
    }

    // Array zonder type hints
    public function getAllUsers()
    {
        $users = array();

        foreach ($this->userService->all() as $user) {
            array_push($users, $user);
        }

        return $users;
    }

    // Oude array syntax
    public function createUser($data)
    {
        $defaults = array(
            'role' => 'user',
            'active' => true,
            'created_at' => date('Y-m-d H:i:s')
        );

        $userData = array_merge($defaults, $data);

        return $this->userService->create($userData);
    }

    // Geen void return type
    public function deleteUser($id)
    {
        $user = $this->getUser($id);

        if ($user != null) {
            $this->userService->delete($id);
            $this->logger->info("Deleted user: " . $id);
        }
    }

    // Unused private method (dead code)
    private function unusedMethod()
    {
        return "This is never called";
    }
}
