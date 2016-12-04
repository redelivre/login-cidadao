Login Cidadão
=============

Login Cidadão é um software livre que atua como um provedor de identidade
implementado protocolos como **OpenID Connect** e **OAuth 2**. Seu foco de
utilização é em entidades governamentais, entretanto não há nenhuma restrição
no projeto que impeça seu isso por entes privados.

Instalação
----------

### Arquitetura
O Login Cidadão é escrito em PHP com apoio do framework **Symfony 2**
e utiliza **Doctrine** para persistência de dados, suportando tanto
**MySQL** quanto **PostgreSQL**. Além disso, você vai precisar de um
servidor **memcached** para armazenamento de sessões e cache de metadados.

### O que você vai precisar
Essa documentação não irá descrever os passos necessários para instalar
os componentes necessários que não sejam específicos do Login Cidadão
visto que a configuração de cada um pode variar significativamente de
acordo com as características e necessidades de segurança de cada
organização. Além disso, por se tratar de softwares mantidos por terceiros,
os passos necessários por sua instalação podem variar com o tempo ou com
a versão escolhida.

Portanto, você deve providenciar a instalação dos seguintes componentes:

  * **Linux ou Mac OS X**: o Login Cidadão suporta oficialmente apenas
Linux e, consequentemente, Mac. Você provavelmente conseguirá instalar em
sistemas como *Microsoft Windows*, entretanto não oferecemos suporte para
esse procedimento;
  * **Certificado para HTTPS**: o Login Cidadão **não suporta HTTP**, sendo 
obrigatório o uso de HTTPS. Caso sua organização não tenha um provedor de
certificados previamente acordado, recomendamos o uso do
[Let's Encrypt](https://letsencrypt.org/), onde você poderá obter
certificados válidos de forma totalmente gratuita. Seu uso é simples e
os certificados podem ser renovados automaticamente;
  * [**nginx**](https://nginx.org/) ou [**Apache**](https://httpd.apache.org/):
para receber as requisições HTTPS, você precisará de um desses dois
softwares. Recomendamos o [**nginx**](https://nginx.org/) por apresentar
boa performance;
  * **PHP 5** ou **7**: a forma mais simples de instalar o Login Cidadão é utilizando
PHP 5. Caso você queira utilizar PHP 7 serão necessárias algumas modificações
uma vez que certas extensões tais como a `memcache` (sem um 'd' no final)
não estão disponíveis;
  * **PHP Extensions**: além do PHP, serão necessárias algumas extensões
para o correto funcionamento do Login Cidadão, tais como `curl`,
`intl`, `memcache`. Note que a disponibilidade de algumas extensões pode
variar de acordo com a versão do PHP que você estiver usando;
  * [**php-fpm**](https://php-fpm.org/): esse será o componente responsável por processar as
requisições PHP. Caso você escolha usar *Apache*, é possível fazer com que
ele processe também as requisições PHP;
  * [**PostgreSQL**](https://www.postgresql.org/) ou
[**MySQL**](https://www.mysql.com/): as informações do Login Cidadão podem
ser armazenadas em bancos de dados **PostgreSQL** ou **MySQL**. Escolha o
que melhor lhe atende;
  * [**memcached**](https://memcached.org/): as informações de sessão e o
cache do *Doctrine* são armazenados em um servidor **memcached**;
  * [**Git**](https://git-scm.com/): é o sistema de controle de versão
utilizado no Login Cidadão. Você precisará dele para baixar e manter
atualizado o código fonte;
  * [**composer**](http://getcomposer.org/): é um gerenciador de
dependências para PHP. Recomendamos que você instale ele globalmente, de
forma que ele seja acessível a partir de qualquer diretório do sistema;
  * [**Node.js**](https://nodejs.org/en/): node é um runtime JavaScript
e é utilizado pelo Login Cidadão para gerar assets tais como scripts e
CSS.

### Tipos de Instalação do Login Cidadão

Atualmente o Login Cidadão é uma aplicação inteira e não um *Bundle*. Isso
quer dizer que você não o instala em uma aplicação *Symfony 2* já existente
como você faria normalmente com um *Bundle*.

Por esse motivo, antes de iniciar a instalação do Login Cidadão você precisa
decidir se deseja customizar sua instalação (trocando cores, imagens, textos)
ou se a versão padrão já lhe atende.

Existem planos para transformar o Login Cidadão em um *Bundle*, o que irá
facilitar o processo de instalação e customização, mas ainda não temos
previsão para que isso ocorra.

#### Vou personalizar minha instalação
Se você deseja fazer alterações em sua instalação, é necessário que você
primeiramente crie um fork do projeto base. Dessa forma você conseguirá
manter sua instalação atualizada com o projeto principal sem perder suas
modificações.

Para orientações sobre como fazer um fork, verifique a
[documentação do GitHub](https://help.github.com/articles/fork-a-repo/).

#### Não pretendo modificar nada
Utilizar o Login Cidadão sem nenhuma alteração é a forma mais fácil e rápida
de iniciar. Para isso, basta clonar o repositório principal conforme será
demonstrado no passo-a-passo.

Se você não tem experiência com Git, você pode consultar a documentação
do GitHub sobre [como clonar um repositório](https://help.github.com/articles/cloning-a-repository/).

### Instalando o Login Cidadão

A partir desse ponto, consideramos que você já tenha instalado os softwares
necessários listados nos itens anteriores além de ter conferido o nosso
`README.md`.

O primeiro passo será obter o código do Login Cidadão. Se você está usando
um fork, troque o endereço do repositório pelo endereço de seu fork.

```
$ git clone https://github.com/redelivre/login-cidadao.git
```

Após ter baixado o código do Login Cidadão através do Git, você precisará
instalar as dependências do projeto. Entre no diretório que foi criado pelo
Git e instale as dependências utilizando Composer.

```
$ cd login-cidadao
$ composer install
```

Essa etapa pode levar vários minutos já que são diversas dependências.
Além das bibliotecas solicitadas pelo Login Cidadão, o Composer também
verificará se o PHP instalado em seu sistema possui as extensões que
são necessárias para a execução das dependências.

Caso você não tenha alguma das extensões basta instalar usando o
gerenciador de pacotes do seu sistema. Por exemplo, se o composer detectar
que está faltando a `ext-curl` você pode solicitar a instalação do pacote
`php5-curl` ou `php7.0-curl`, dependendo da versão do PHP utilizada.

```
$ sudo apt-get install php5-curl
```

Após a conclusão da instalação das dependências o composer executará
scripts de pós-instalação do Symfony onde serão solicitados os parâmetros
do seu ambiente de forma iterativa. Você pode consultar a descrição dos
parâmetros no arquivo `app/config/parameters.yml.dist`.

Os parâmetros da sua instância serão salvos no arquivo `app/config/parameters.yml`,
onde você pode alterar posteriormente caso queira fazer algum ajuste.

É importante lembrar de limpar o cache da aplicação a cada alteração feita
no diretório `app/config`.

    $ ./app/console cache:clear

Depois de configurar seu `parameters.yml` você já deve conseguir criar a
estrutura necessária do banco de dados e, em seguida, popular com os dados
iniciais.

    $ ./app/console doctrine:schema:update --force
    $ ./app/console lc:database:populate batch/

Por último, execute o comando `lc:deploy` que irá preparar sua instalação
para execução em produção. Esse comando limpará o cache de metadados do
Doctrine, verificará se o banco de dados está atualizado e providenciará a
geração dos assets necessários.

    $ ./app/console lc:deploy
