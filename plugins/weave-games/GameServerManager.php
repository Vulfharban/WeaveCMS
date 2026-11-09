<?php
/**
 * ============================================================================
 * Weave Games – GameServerManager
 * ============================================================================
 * Verwaltet Game-Server-Instanzen:
 * - Serverübersicht mit Live-Ping, Status & Spieleranzahl
 * - Serverstart / Stop / Neustart lokal & remote
 * - Parameterübergabe für Windows & Linux (Ports, Worlds, Max Players, Seed)
 * - SSH-Fernsteuerung für externe Linux- & Windows-Server
 * - Geeignet für Desktop-Spiele (Multiplayer RPGs), Browsergame-Hosts & Dedicated Servers
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager')) {
    exit;
}

class GameServerManager {

    public const OS_WINDOWS = 'windows';
    public const OS_LINUX   = 'linux';

    public const CONN_LOCAL = 'local';
    public const CONN_SSH   = 'ssh';

    public static function getServersFilePath(): string {
        return dirname(__DIR__, 2) . '/data/games_servers.json';
    }

    public static function getServers(): array {
        $file = self::getServersFilePath();
        if (!file_exists($file)) {
            return self::getDefaultServers();
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : self::getDefaultServers();
    }

    public static function saveServers(array $servers): bool {
        $file = self::getServersFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($servers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $json) === false) {
            return false;
        }
        return rename($tmp, $file);
    }

    public static function getDefaultServers(): array {
        return [
            [
                'id'                => 'srv_voxel_01',
                'name'              => 'Voxel World Alpha (Server 01)',
                'game_slug'         => 'voxel-adventure',
                'host'              => '127.0.0.1',
                'port'              => 7777,
                'query_port'        => 7778,
                'os_type'           => self::OS_WINDOWS,
                'connection_type'   => self::CONN_LOCAL,
                'executable_path'   => 'C:/Games/VoxelWorld/server.exe',
                'working_dir'       => 'C:/Games/VoxelWorld',
                'custom_parameters' => '--port 7777 --world world_01 --max-players 32 --monsters normal',
                'start_command'     => '',
                'stop_command'      => '',
                'ssh_host'          => '',
                'ssh_port'          => 22,
                'ssh_user'          => '',
                'ssh_auth'          => 'password',
                'ssh_pass'          => '',
                'ssh_key'           => '',
                'max_players'       => 32,
                'current_players'   => 0,
                'status'            => 'offline',
                'last_ping_ms'      => null,
                'last_checked'      => null,
                'auto_restart'      => true,
                'active'            => true
            ],
            [
                'id'                => 'srv_voxel_cloud',
                'name'              => 'Voxel Cloud Cluster (Frankfurt)',
                'game_slug'         => 'voxel-adventure',
                'host'              => '195.201.10.42',
                'port'              => 7777,
                'query_port'        => 7778,
                'os_type'           => self::OS_LINUX,
                'connection_type'   => self::CONN_SSH,
                'executable_path'   => '/opt/gameserver/server',
                'working_dir'       => '/opt/gameserver',
                'custom_parameters' => '--port 7777 --world world_cloud --tick-rate 60 --max-players 64',
                'start_command'     => 'systemctl start gameserver.service',
                'stop_command'      => 'systemctl stop gameserver.service',
                'ssh_host'          => '195.201.10.42',
                'ssh_port'          => 22,
                'ssh_user'          => 'game_admin',
                'ssh_auth'          => 'key',
                'ssh_pass'          => '',
                'ssh_key'           => '~/.ssh/id_rsa',
                'max_players'       => 64,
                'current_players'   => 0,
                'status'            => 'offline',
                'last_ping_ms'      => null,
                'last_checked'      => null,
                'auto_restart'      => true,
                'active'            => true
            ]
        ];
    }

    public static function getServerById(string $id): ?array {
        $servers = self::getServers();
        foreach ($servers as $s) {
            if (($s['id'] ?? '') === $id) {
                return $s;
            }
        }
        return null;
    }

    public static function saveServer(array $data): bool {
        $servers = self::getServers();
        $id = trim($data['id'] ?? '');

        if (empty($id)) {
            $slug = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($data['name'] ?? 'srv'));
            $id = 'srv_' . $slug . '_' . bin2hex(random_bytes(2));
        }

        $existing = false;
        foreach ($servers as &$s) {
            if (($s['id'] ?? '') === $id) {
                $s['name']              = trim($data['name'] ?? $s['name']);
                $s['game_slug']         = trim($data['game_slug'] ?? $s['game_slug']);
                $s['host']              = trim($data['host'] ?? $s['host']);
                $s['port']              = (int)($data['port'] ?? $s['port']);
                $s['query_port']        = (int)($data['query_port'] ?? $s['query_port']);
                $s['os_type']           = in_array($data['os_type'] ?? '', [self::OS_WINDOWS, self::OS_LINUX], true) ? $data['os_type'] : $s['os_type'];
                $s['connection_type']   = in_array($data['connection_type'] ?? '', [self::CONN_LOCAL, self::CONN_SSH], true) ? $data['connection_type'] : $s['connection_type'];
                $s['executable_path']   = trim($data['executable_path'] ?? $s['executable_path']);
                $s['working_dir']       = trim($data['working_dir'] ?? $s['working_dir']);
                $s['custom_parameters'] = trim($data['custom_parameters'] ?? $s['custom_parameters']);
                $s['start_command']     = trim($data['start_command'] ?? $s['start_command']);
                $s['stop_command']      = trim($data['stop_command'] ?? $s['stop_command']);
                $s['ssh_host']          = trim($data['ssh_host'] ?? $s['ssh_host']);
                $s['ssh_port']          = (int)($data['ssh_port'] ?? ($s['ssh_port'] ?: 22));
                $s['ssh_user']          = trim($data['ssh_user'] ?? $s['ssh_user']);
                $s['ssh_auth']          = in_array($data['ssh_auth'] ?? '', ['password', 'key'], true) ? $data['ssh_auth'] : 'password';
                if (!empty($data['ssh_pass'])) {
                    $s['ssh_pass']      = $data['ssh_pass'];
                }
                $s['ssh_key']           = trim($data['ssh_key'] ?? $s['ssh_key']);
                $s['max_players']       = max(1, (int)($data['max_players'] ?? $s['max_players']));
                $s['auto_restart']      = !empty($data['auto_restart']);
                $s['active']            = !empty($data['active']);
                $existing = true;
                break;
            }
        }
        unset($s);

        if (!$existing) {
            $servers[] = [
                'id'                => $id,
                'name'              => trim($data['name'] ?? 'Neuer Game Server'),
                'game_slug'         => trim($data['game_slug'] ?? 'voxel-adventure'),
                'host'              => trim($data['host'] ?? '127.0.0.1'),
                'port'              => (int)($data['port'] ?? 7777),
                'query_port'        => (int)($data['query_port'] ?? 7778),
                'os_type'           => in_array($data['os_type'] ?? '', [self::OS_WINDOWS, self::OS_LINUX], true) ? $data['os_type'] : self::OS_WINDOWS,
                'connection_type'   => in_array($data['connection_type'] ?? '', [self::CONN_LOCAL, self::CONN_SSH], true) ? $data['connection_type'] : self::CONN_LOCAL,
                'executable_path'   => trim($data['executable_path'] ?? ''),
                'working_dir'       => trim($data['working_dir'] ?? ''),
                'custom_parameters' => trim($data['custom_parameters'] ?? ''),
                'start_command'     => trim($data['start_command'] ?? ''),
                'stop_command'      => trim($data['stop_command'] ?? ''),
                'ssh_host'          => trim($data['ssh_host'] ?? ''),
                'ssh_port'          => (int)($data['ssh_port'] ?? 22),
                'ssh_user'          => trim($data['ssh_user'] ?? ''),
                'ssh_auth'          => in_array($data['ssh_auth'] ?? '', ['password', 'key'], true) ? $data['ssh_auth'] : 'password',
                'ssh_pass'          => $data['ssh_pass'] ?? '',
                'ssh_key'           => trim($data['ssh_key'] ?? ''),
                'max_players'       => max(1, (int)($data['max_players'] ?? 32)),
                'current_players'   => 0,
                'status'            => 'offline',
                'last_ping_ms'      => null,
                'last_checked'      => null,
                'auto_restart'      => !empty($data['auto_restart']),
                'active'            => !empty($data['active'])
            ];
        }

        return self::saveServers($servers);
    }

    public static function deleteServer(string $id): bool {
        $servers = self::getServers();
        $initial = count($servers);
        $servers = array_values(array_filter($servers, fn($s) => ($s['id'] ?? '') !== $id));
        if (count($servers) < $initial) {
            return self::saveServers($servers);
        }
        return false;
    }

    /**
     * Prüft via Socket-Ping die Erreichbarkeit und Latenz des Servers.
     */
    public static function pingServer(string $host, int $port, float $timeout = 0.8): array {
        $startTime = microtime(true);
        $errNo = 0;
        $errStr = '';

        $fp = @fsockopen($host, $port, $errNo, $errStr, $timeout);
        if ($fp) {
            $latency = round((microtime(true) - $startTime) * 1000);
            fclose($fp);
            return [
                'online'     => true,
                'ping_ms'    => (int)$latency,
                'status'     => 'online',
                'error'      => null
            ];
        }

        return [
            'online'     => false,
            'ping_ms'    => null,
            'status'     => 'offline',
            'error'      => $errStr ?: 'Verbindung abgewiesen oder Timeout'
        ];
    }

    /**
     * Aktualisiert den Status aller registrierten Server.
     */
    public static function refreshAllServerStatuses(): array {
        $servers = self::getServers();
        foreach ($servers as &$s) {
            if (empty($s['active'])) {
                $s['status'] = 'disabled';
                continue;
            }
            $res = self::pingServer($s['host'], (int)$s['port']);
            $s['status'] = $res['status'];
            $s['last_ping_ms'] = $res['ping_ms'];
            $s['last_checked'] = date('Y-m-d H:i:s');
        }
        unset($s);

        self::saveServers($servers);
        return $servers;
    }

    /**
     * Startet einen Game-Server (lokal oder via SSH).
     */
    public static function startServer(string $serverId, ?string &$log = null): bool {
        $server = self::getServerById($serverId);
        if (!$server) {
            $log = "Server '{$serverId}' nicht gefunden.";
            return false;
        }

        if ($server['connection_type'] === self::CONN_SSH) {
            return self::executeSshAction($server, 'start', $log);
        }

        // Lokaler Serverstart (Windows oder Linux)
        return self::executeLocalAction($server, 'start', $log);
    }

    /**
     * Stoppt einen Game-Server.
     */
    public static function stopServer(string $serverId, ?string &$log = null): bool {
        $server = self::getServerById($serverId);
        if (!$server) {
            $log = "Server '{$serverId}' nicht gefunden.";
            return false;
        }

        if ($server['connection_type'] === self::CONN_SSH) {
            return self::executeSshAction($server, 'stop', $log);
        }

        return self::executeLocalAction($server, 'stop', $log);
    }

    /**
     * Startet einen Game-Server neu.
     */
    public static function restartServer(string $serverId, ?string &$log = null): bool {
        $logStop = '';
        $logStart = '';
        $stopped = self::stopServer($serverId, $logStop);
        sleep(1);
        $started = self::startServer($serverId, $logStart);
        $log = "Stop: {$logStop} | Start: {$logStart}";
        return $started;
    }

    /**
     * Führt lokale Serverbefehle aus (Windows / Linux).
     */
    protected static function executeLocalAction(array $server, string $action, ?string &$log = null): bool {
        $isWin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') || ($server['os_type'] === self::OS_WINDOWS);
        $cmd = '';

        if ($action === 'start') {
            if (!empty($server['start_command'])) {
                $cmd = $server['start_command'];
            } else {
                $exe = $server['executable_path'];
                $params = $server['custom_parameters'];
                if ($isWin) {
                    $cmd = "start \"{$server['name']}\" /B \"{$exe}\" {$params}";
                } else {
                    $cmd = "nohup \"{$exe}\" {$params} > /dev/null 2>&1 &";
                }
            }
        } elseif ($action === 'stop') {
            if (!empty($server['stop_command'])) {
                $cmd = $server['stop_command'];
            } else {
                $exeName = basename($server['executable_path']);
                if ($isWin) {
                    $cmd = "taskkill /F /IM \"{$exeName}\"";
                } else {
                    $cmd = "killall \"{$exeName}\"";
                }
            }
        }

        if (empty($cmd)) {
            $log = "Kein Befehl für Aktion '{$action}' hinterlegt.";
            return false;
        }

        $workDir = !empty($server['working_dir']) && is_dir($server['working_dir']) ? $server['working_dir'] : null;
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = @proc_open($cmd, $descriptors, $pipes, $workDir);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $log = "Befehl ausgeführt: {$cmd} [Exit: {$exitCode}]" . (!empty($out) ? " Output: " . trim($out) : '') . (!empty($err) ? " Err: " . trim($err) : '');
            
            // Status in DB aktualisieren
            $newStatus = ($action === 'start') ? 'starting' : 'offline';
            self::updateServerStatus($server['id'], $newStatus);
            return true;
        }

        $log = "Konnte Prozess '{$cmd}' nicht starten.";
        return false;
    }

    /**
     * Führt Remote-Aktionen über SSH aus (Windows / Linux).
     */
    protected static function executeSshAction(array $server, string $action, ?string &$log = null): bool {
        $sshHost = !empty($server['ssh_host']) ? $server['ssh_host'] : $server['host'];
        $sshPort = !empty($server['ssh_port']) ? (int)$server['ssh_port'] : 22;
        $sshUser = $server['ssh_user'] ?? 'root';
        $sshKey = $server['ssh_key'] ?? '';
        $sshPass = $server['ssh_pass'] ?? '';

        $remoteCmd = '';
        if ($action === 'start') {
            $remoteCmd = !empty($server['start_command']) 
                ? $server['start_command'] 
                : "cd \"{$server['working_dir']}\" && nohup \"{$server['executable_path']}\" {$server['custom_parameters']} > /dev/null 2>&1 &";
        } elseif ($action === 'stop') {
            $remoteCmd = !empty($server['stop_command'])
                ? $server['stop_command']
                : "killall -9 \"". basename($server['executable_path']) ."\"";
        }

        if (empty($remoteCmd)) {
            $log = "SSH-Fehler: Kein Befehl definiert.";
            return false;
        }

        // Falls PHP SSH2-Erweiterung verfügbar ist:
        if (function_exists('ssh2_connect')) {
            $connection = @ssh2_connect($sshHost, $sshPort);
            if ($connection) {
                $authOk = false;
                if (!empty($sshKey) && file_exists($sshKey)) {
                    $authOk = @ssh2_auth_pubkey_file($connection, $sshUser, $sshKey . '.pub', $sshKey, $sshPass);
                } elseif (!empty($sshPass)) {
                    $authOk = @ssh2_auth_password($connection, $sshUser, $sshPass);
                }

                if ($authOk) {
                    $stream = @ssh2_exec($connection, $remoteCmd);
                    if ($stream) {
                        stream_set_blocking($stream, true);
                        $output = stream_get_contents($stream);
                        fclose($stream);
                        $log = "SSH-Befehl erfolgreich übergeben: {$remoteCmd}";
                        self::updateServerStatus($server['id'], ($action === 'start' ? 'starting' : 'offline'));
                        return true;
                    }
                }
            }
        }

        // Fallback: CLI ssh Client aufrufen
        $sshCli = "ssh -p {$sshPort} -o StrictHostKeyChecking=no -o ConnectTimeout=5";
        if (!empty($sshKey) && file_exists($sshKey)) {
            $sshCli .= " -i " . escapeshellarg($sshKey);
        }
        $fullCmd = "{$sshCli} " . escapeshellarg("{$sshUser}@{$sshHost}") . " " . escapeshellarg($remoteCmd);

        $out = [];
        $ret = 0;
        @exec($fullCmd, $out, $ret);

        $log = "SSH CLI [{$sshHost}:{$sshPort}] ausgeführt: {$fullCmd} [Return: {$ret}] Output: " . implode(' ', $out);
        self::updateServerStatus($server['id'], ($action === 'start' ? 'starting' : 'offline'));
        return ($ret === 0);
    }

    protected static function updateServerStatus(string $serverId, string $status): void {
        $servers = self::getServers();
        foreach ($servers as &$s) {
            if (($s['id'] ?? '') === $serverId) {
                $s['status'] = $status;
                $s['last_checked'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($s);
        self::saveServers($servers);
    }
}

