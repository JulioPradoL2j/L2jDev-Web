<?php
return [
    'site' => [
        'name' => 'Lineage 2 Dev',
        'base_url' => 'http://localhost',
		'owner' => 'BAN-L2JDev',
		'discord' => 'https://discord.gg/userId',
		'whatsapp_link' => 'https://wa.me/5564984083891?text=Ol%C3%A1%21%20Preciso%20de%20suporte%20no%20Lineage%202%20Dev.',
  		'facebook' => 'https://www.facebook.com/JuvenilJ/',
		
    ],
	
	'debug' => [
        'enabled' => false,                 // false em produção final
        'log_file' => __DIR__ . '/logs/debug.log',
        'display_errors' => false,          // false em produção final
    ],

    'ranking' => [
        'limit' => 5,
		
    ],
	
	'siege' => [
        'limit' => 1,
		
    ],
	
	'news' => [
        'limit' => 5,
		
    ],
	
	
	'raid' => [
        'limitPage' => 5,
		'limitlevel' => 60,
    ],
	
	'suffix' => [
		'enabled' => true,          // se false: não mostra checkbox nem select e nunca usa sufixo
		'optional' => true,         // se true: checkbox aparece e usuário escolhe usar ou não
		'default_use' => true,     // estado inicial do checkbox (marcado ou não)
	
		// opções permitidas
		'options' => [
		'BR'   => 'br',
		'MAIN' => 'main',
		'VIP'  => 'vip',
		],
	],
  
    'db' => [
        'host' => 'localhost',
        'name' => 'l2jdb',
        'user' => 'root',
        'pass' => 'root',
    ],

    // =========================
    // SERVERS (STATUS / UI)
    // =========================
    'servers' => [
        // Host usado para o TESTE de porta (ideal: IP interno/localhost se o site estiver na mesma máquina)
        'check_host' => '127.0.0.1',

        // Host exibido para o usuário (ideal: domínio ou IP público)
        'display_host' => 'auth.lineage.com',

        'login' => [
            'port' => 2106,
            'name' => 'Login Server',
        ],
        'game' => [
            'port' => 7777,
            'name' => 'Game Server',
        ],

        // timeout do teste de porta (segundos)
        'timeout' => 0.6,
    ],
	
	'rates' => [
        // Host usado para o TESTE de porta (ideal: IP interno/localhost se o site estiver na mesma máquina)
        'xp' => '500',

        // Host exibido para o usuário (ideal: domínio ou IP público)
        'sp' => '500',
		'adena' => '10',
		'drop' => '5',
		'enchant_min' => '3',
		'enchant_max' => '25',

    ],
		'downloads' => [
		'title' => 'Downloads Oficiais',
		'subtitle' => 'Interlude clássico (2003–2006) com infraestrutura moderna em 2026. Baixe, instale e jogue.',
		
		// Recomendado: use links diretos (CDN, Google Drive direto, Mega, etc.)
		'items' => [
			[
				'id' => 'updater',
				'name' => 'Updater (Recomendado)',
				'desc' => 'Instala e mantém seu client sempre atualizado automaticamente.',
				'tag'  => 'Mais fácil',
				'size' => '≈ 25 MB',
				'url'  => 'https://seu-link.com/Updater.zip',
				'icon' => '⬇', // pode trocar depois
				'primary' => true,
			],
			[
				'id' => 'client',
				'name' => 'Cliente Interlude Completo',
				'desc' => 'Client completo pronto para jogar. Ideal para instalação limpa.',
				'tag'  => 'Completo',
				'size' => '≈ 5.2 GB',
				'url'  => 'https://seu-link.com/Client-Interlude.zip',
				'icon' => '📦',
			],
			[
				'id' => 'patch',
				'name' => 'Patch Detona (Manual)',
				'desc' => 'Atualização manual para quem já tem um client Interlude.',
				'tag'  => 'Manual',
				'size' => '≈ 300 MB',
				'url'  => 'https://seu-link.com/Patch.zip',
				'icon' => '🧩',
			],
			[
				'id' => 'system',
				'name' => 'System + L2.ini',
				'desc' => 'Arquivos de system e configurações (caso precise reparar).',
				'tag'  => 'Reparo',
				'size' => '≈ 80 MB',
				'url'  => 'https://seu-link.com/System.zip',
				'icon' => '⚙',
			],
		],
	
		// Pré-requisitos (links opcionais)
		'requirements' => [
			[
				'name' => 'DirectX 9.0c',
				'desc' => 'Melhor compatibilidade para Interlude em PCs modernos.',
				'url'  => 'https://www.microsoft.com/en-us/download/details.aspx?id=8109',
			],
			[
				'name' => 'Microsoft Visual C++ (x86)',
				'desc' => 'Pacotes redistribuíveis necessários em alguns sistemas.',
				'url'  => 'https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist',
			],
		],
	
		// Checksums (opcional)
		'checksums' => [
			// 'client' => ['sha256' => '...', 'md5' => '...'],
		],
	
		// Notas rápidas
		'notes' => [
			'Extraia o jogo em uma pasta fora de “Arquivos de Programas”. Ex.: C:\Games\Lineage2Detona',
			'Execute o updater como Administrador na primeira vez (Windows).',
			'Adicione a pasta do jogo como exceção no antivírus caso haja falso-positivo.',
		],
	],
	
];
