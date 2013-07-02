Módulo Magento Paybras
==============================

Instruções
--------------

Recomendamos que primeiramente realize backup completo do seu site, banco de dados e arquivos. A Evolucard não se responsabiliza por quaisquer danos ou prejuízos financeiros decorrentes da má utilização ou instalação desse módulo.

Requerimentos
--------------

- Magento 1.4.2.0 ou superior
- PHP 5.2.0 ou superior

Instalações
--------------

Faça o download do arquivo .zip do módulo (provavelmente já o fez).
Descompactar os arquivos para uma pasta qualquer em seu computador, por exemplo, nova pasta.
Envie via FTP todos os arquivos e pastas que foram descompactados em sua pasta (ex: nova pasta).

Configuração
--------------

Limpar o cachê do Magento através do menu SISTEMA -> GERENCIAMENTO DE CACHE
Clique em SISTEMA -> CONFIGURAÇÃO
Clique na seção VENDAS no subitem MÉTODOS DE PAGAMENTOS e abra a seção PAYBRAS.
Coloque nos devidos campos as informações referentes a códigos de integração, email, e outras configurações.
Para capturar automaticamente as notificações de status, além de ativar o campo "Habilitar recebimento de Notificação", você deve utilizar a seguinte url de integração: http://<urldaloja>/index.php/paybras/standard/captura
Para colocar o módulo em Produção, escolha a opção PRODUÇÃO no campo AMBIENTE.

Customização
--------------

É possível efetuar modificações visuais via CSS (skin/frontend/default/default/css/paybras.css) e modificando o HTML em (app/design/frontend/base/default/template/xpd/paybras/form/cc.phtml).