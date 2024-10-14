# Framework KaririCode: Componente PropertyInspector

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

Um componente poderoso e flexível para inspecionar e processar propriedades de objetos baseado em atributos personalizados no Framework KaririCode, fornecendo recursos avançados para validação, sanitização e análise de propriedades em aplicações PHP.

## Índice

- [Características](#características)
- [Instalação](#instalação)
- [Uso](#uso)
  - [Uso Básico](#uso-básico)
  - [Uso Avançado](#uso-avançado)
- [Integração com Outros Componentes KaririCode](#integração-com-outros-componentes-kariricode)
- [Desenvolvimento e Testes](#desenvolvimento-e-testes)
- [Licença](#licença)
- [Suporte e Comunidade](#suporte-e-comunidade)
- [Agradecimentos](#agradecimentos)

## Características

- Fácil inspeção e processamento de propriedades de objetos baseados em atributos personalizados
- Suporte para validação e sanitização de valores de propriedades
- Manipulação flexível de atributos através de manipuladores de atributos personalizados
- Integração perfeita com outros componentes KaririCode (Serializer, Validator, Normalizer)
- Arquitetura extensível permitindo atributos e manipuladores personalizados
- Construído sobre as interfaces KaririCode\Contract para máxima flexibilidade

## Instalação

O componente PropertyInspector pode ser facilmente instalado via Composer, que é o gerenciador de dependências recomendado para projetos PHP.

Para instalar o componente PropertyInspector em seu projeto, execute o seguinte comando no seu terminal:

```bash
composer require kariricode/property-inspector
```

Este comando adicionará automaticamente o PropertyInspector ao seu projeto e instalará todas as dependências necessárias.

### Requisitos

- PHP 8.1 ou superior
- Composer

### Instalação Manual

Se preferir não usar o Composer, você pode baixar o código-fonte diretamente do [repositório GitHub](https://github.com/KaririCode-Framework/kariricode-property-inspector) e incluí-lo manualmente em seu projeto. No entanto, recomendamos fortemente o uso do Composer para facilitar o gerenciamento de dependências e atualizações.

Após a instalação, você pode começar a usar o PropertyInspector em seu projeto PHP imediatamente. Certifique-se de incluir o autoloader do Composer em seu script:

```php
require_once 'vendor/autoload.php';
```

## Uso

### Uso Básico

1. Defina seus atributos personalizados e entidade:

```php
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    public function __construct(public readonly array $rules) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Sanitize
{
    public function __construct(public readonly string $method) {}
}

class Usuario
{
    public function __construct(
        #[Validate(['required', 'string', 'min:3'])]
        #[Sanitize('trim')]
        public string $nome,
        #[Validate(['required', 'email'])]
        #[Sanitize('lowercase')]
        public string $email,
        #[Validate(['required', 'integer', 'min:18'])]
        public int $idade
    ) {}
}
```

2. Crie um manipulador de atributos personalizado:

```php
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;

class ManipuladorAtributoPersonalizado implements PropertyAttributeHandler
{
    public function handleAttribute(object $object, string $propertyName, object $attribute, mixed $value): ?string
    {
        if ($attribute instanceof Validate) {
            return $this->validar($propertyName, $value, $attribute->rules);
        }
        if ($attribute instanceof Sanitize) {
            return $this->sanitizar($value, $attribute->method);
        }
        return null;
    }

    // Implemente os métodos validar e sanitizar...
}
```

3. Use o PropertyInspector:

```php
use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\PropertyInspector;

$analisadorAtributos = new AttributeAnalyzer(Validate::class);
$inspetorPropriedades = new PropertyInspector($analisadorAtributos);
$manipulador = new ManipuladorAtributoPersonalizado();

$usuario = new Usuario('Walmir Silva', 'walmir@exemplo.com', 25);

$resultados = $inspetorPropriedades->inspect($usuario, $manipulador);
```

### Uso Avançado

Você pode criar regras de validação e sanitização mais complexas e até combinar o PropertyInspector com outros componentes como o ProcessorPipeline para fluxos de processamento mais avançados.

## Integração com Outros Componentes KaririCode

O componente PropertyInspector é projetado para trabalhar perfeitamente com outros componentes KaririCode:

- **KaririCode\Serializer**: Use o PropertyInspector para validar e sanitizar dados antes da serialização.
- **KaririCode\Validator**: Integre lógica de validação personalizada com atributos do PropertyInspector.
- **KaririCode\Normalizer**: Use o PropertyInspector para normalizar propriedades de objetos baseadas em atributos.

## Desenvolvimento e Testes

Para fins de desenvolvimento e teste, este pacote usa Docker e Docker Compose para garantir consistência em diferentes ambientes. Um Makefile é fornecido para conveniência.

### Pré-requisitos

- Docker
- Docker Compose
- Make (opcional, mas recomendado para facilitar a execução de comandos)

### Configuração de Desenvolvimento

1. Clone o repositório:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-property-inspector.git
   cd kariricode-property-inspector
   ```

2. Configure o ambiente:

   ```bash
   make setup-env
   ```

3. Inicie os contêineres Docker:

   ```bash
   make up
   ```

4. Instale as dependências:
   ```bash
   make composer-install
   ```

### Comandos Make Disponíveis

- `make up`: Inicia todos os serviços em segundo plano
- `make down`: Para e remove todos os contêineres
- `make build`: Constrói imagens Docker
- `make shell`: Acessa o shell do contêiner PHP
- `make test`: Executa os testes
- `make coverage`: Executa a cobertura de testes com formatação visual
- `make cs-fix`: Executa o PHP CS Fixer para corrigir o estilo do código
- `make quality`: Executa todos os comandos de qualidade (cs-check, test, security-check)

Para uma lista completa de comandos disponíveis, execute:

```bash
make help
```

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Suporte e Comunidade

- **Documentação**: [https://kariricode.org/docs/property-inspector](https://kariricode.org/docs/property-inspector)
- **Rastreador de Problemas**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-property-inspector/issues)
- **Comunidade**: [Comunidade KaririCode Club](https://kariricode.club)

## Agradecimentos

- A equipe do Framework KaririCode e colaboradores.
- Inspirado em padrões de programação baseada em atributos e reflexão em aplicações PHP modernas.

---

Construído com ❤️ pela equipe KaririCode. Capacitando desenvolvedores para criar aplicações PHP mais robustas e flexíveis.
