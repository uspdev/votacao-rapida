[README](../README.md) |
Descrição funcional | 
[Descrição técnica](descricao_tecnica.md)

---

# Descrição Funcional — Sistema de Votação Rápida

O **Votação Rápida** é uma plataforma desenvolvida para modernizar, agilizar e dar segurança aos processos de tomada de decisão e eleições em reuniões de colegiados da Universidade de São Paulo (tais como Congregações, Conselhos Departamentais, Conselhos Centrais, Comissões e Assembleias).

O sistema suporta reuniões **remotas** (videoconferência via Google Meet/Zoom), **presenciais** e **híbridas**, oferecendo interfaces específicas para cada perfil envolvido no processo.

---

## 1. Tipos de Votação Suportados

A plataforma suporta duas modalidades de votação, definidas no momento do cadastro de cada questão:

### 1.1. Votação Aberta (Nominal)
* **Objetivo:** Votações públicas onde a manifestação de cada membro deve constar em ata e ser pública para todos os presentes.
* **Comportamento:** O eleitor recebe um token nominal associado ao seu número USP e nome. Na apuração final e na projeção do telão, o voto de cada participante é listado individualmente (ex: "Fulano de Tal: Favorável").
* **Alternativas Típicas:** Por padrão, são sugeridas *"Favorável"*, *"Contrário"* e *"Abstenção"*, podendo ser customizadas.

### 1.2. Votação Fechada (Secreta)
* **Objetivo:** Eleições de dirigentes, representações ou matérias que exigem sigilo do voto individual.
* **Comportamento:** O sistema desacopla a identidade do votante da cédula de votação:
  * Em votações online, os tokens são gerados e distribuídos com controle de acesso anônimo.
  * Em votações presenciais, cada eleitor retira aleatoriamente uma papeleta impressa com QR Code único.
* **Apuração:** O sistema exibe exclusivamente a contagem agregada de votos e porcentagens por alternativa, garantindo a impossibilidade de associar o voto depositado à identidade do eleitor.

---

## 2. Visões do Sistema por Perfil

O fluxo de trabalho organiza-se em quatro interfaces complementares:

```
┌────────────────────────────────────────────────────────┐
│                   GERENTE DA SESSÃO                    │
│      Configura reunião, eleitores e pauta de votos     │
└───────────┬────────────────────────────────┬───────────┘
            │                                │
            ▼                                ▼
┌───────────────────────┐        ┌───────────────────────┐
│     MESA DE APOIO     │        │        ELEITOR        │
│   Conduz as votações  │        │   Recebe token e vota │
└───────────┬───────────┘        └───────────────────────┘
            │
            ▼
┌───────────────────────┐
│    TELÃO / PAINEL     │
│   Projeção na sala    │
└───────────────────────┘
```

---

### 2.1. Visão do Gerente (Secretaria / Organizador da Sessão)

O **Gerente** é o responsável pelo planejamento e configuração da reunião antes e depois de sua realização.

#### Funcionalidades Principais:
1. **Autenticação Segura:**
   * Login direto com a **Senha Única USP** (OAuth corporativo).
   * Acesso liberado para servidores técnicos, estagiários de RH, docentes e administradores cadastrados na configuração do sistema.
2. **Criação e Gestão da Sessão:**
   * **Nome da Sessão:** Identificação exibida em todas as telas e e-mails (ex.: *"150ª Sessão Ordinária da Congregação"*).
   * **Unidade e Data:** Dados de controle e informação temporal no corpo dos e-mails aos votantes.
   * **E-mail de Retorno:** Endereço institucional que recebe cópia de segurança de todos os tokens e mensagens disparadas, servindo também como destinatário para respostas (*reply-to*) dos eleitores.
   * **Link Alternativo:** Campo para inclusão de URL encurtada (ex: `e.usp.br/...`) para facilitar digitação manual em cédulas de papel.
3. **Gestão de Eleitores:**
   * Cadastro individual ou em lote por Número USP (com preenchimento automático de nome e e-mail institucional).
   * Visualização dos tokens gerados para cada votante.
   * Disparo em lote de e-mails contendo os links diretos e individuais de votação.
   * Geração de arquivo PDF formatado com papeletas e QR Codes individuais para impressão e distribuição em reuniões presenciais.
4. **Pauta de Votações:**
   * Cadastro antecipado das questões que serão submetidas a deliberação.
   * Definição de **Nome** (obrigatório), descrição explicativa, tipo (Aberta ou Fechada), alternativas de resposta e ordem de apresentação.
   * Edição e reordenação das questões até o momento do início da votação.
5. **Compartilhamento de Gestão:**
   * Autorização de outros membros da equipe ou secretários como co-gerentes da sessão.
   * Envio por e-mail dos links de acesso à mesa diretora.
6. **Acesso aos Controles da Sessão:**
   * Atalhos diretos para abrir as interfaces de **Apoio**, **Painel** (telão) e **Recepção** (credenciamento).

---

### 2.2. Visão do Apoio (Mesa Diretora / Operadores da Reunião)

O **Painel de Apoio** (`/apoio`) é a interface operacional utilizada pelo presidente da mesa, secretários ou operadores técnicos para conduzir o andamento das votações em tempo real.

#### Características Operacionais:
1. **Acesso Simplificado:**
   * Acessível por link contendo token único da sessão, dispensando telas de login complexas durante a reunião.
2. **Controle Passo a Passo do Ciclo de Vida da Votação:**
   * Cada votação conta com botões coloridos de acordo com a função:
     * <kbd style="background:#17a2b8;color:#fff">Mostrar</kbd> (Ciano): Projeta o enunciado e as opções no telão da assembleia, colocando a questão em análise.
     * <kbd style="background:#28a745;color:#fff">Iniciar</kbd> (Verde): Abre a urna eletrônica para recebimento de votos dos eleitores.
     * <kbd style="background:#ffc107;color:#212529">Pausar</kbd> (Amarelo): Suspende temporariamente o recebimento de votos para esclarecimento de dúvidas da plenária.
     * <kbd style="background:#28a745;color:#fff">Continuar</kbd> (Verde): Reabre a votação pausada.
     * <kbd style="background:#ffc107;color:#212529">Reiniciar</kbd> (Amarelo com confirmação): Zera os votos recebidos e recomeça a votação caso ocorra erro procedimental na sala.
     * <kbd style="background:#007bff;color:#fff">Mostrar resultado</kbd> (Azul): Encerra o recebimento de votos, realiza a apuração e projeta os gráficos de resultado no telão.
     * <kbd style="background:#6c757d;color:#fff">Esconder resultado</kbd> (Cinza): Oculta o resultado da tela e finaliza a votação.
     * <kbd style="background:#dc3545;color:#fff">Excluir</kbd> (Vermelho com confirmação): Remove a votação da sessão.
3. **Criação de Votações Instantâneas:**
   * Durante a reunião, surgem propostas de encaminhamento ou questões de ordem não pautadas.
   * O painel permite criar imediatamente uma votação com preenchimento obrigatório de texto, gerando opções automáticas (*Favorável*, *Contrário*, *Abstenção*).
4. **Trava de Concorrência e Votação Ativa:**
   * Enquanto houver uma votação em andamento (seja em *Exibição*, *Votação*, *Pausa* ou *Resultado*), todos os controles das demais votações e a criação de votação instantânea ficam automaticamente bloqueados.
   * Isso evita que múltiplos operadores abram duas votações simultâneas ou interfiram no telão compartilhado.
5. **Sincronização em Tempo Real Multi-Dispositivo:**
   * Mais de uma pessoa da mesa pode manter o painel de apoio aberto (ex: o presidente em um tablet e o secretário no notebook).
   * A tela consulta atualizações de forma transparente e recarrega alterações feitas por outros operadores, pausando a atualização automática enquanto alguém estiver digitando no campo de texto.

---

### 2.3. Visão do Eleitor (Membros do Colegiado)

O **Eleitor** é o docente, discente ou servidor técnico com direito a voto na sessão.

#### Experiência de Uso:
1. **Recepção da Credencial:**
   * **Reuniões Online / Híbridas:** O eleitor recebe um e-mail institucional com assunto identificando a reunião e o link direto com seu token pessoal de voto.
   * **Reuniões Presenciais:** O eleitor pode receber a papeleta impressa com QR Code, apontando a câmera do celular para abrir a cédula instantaneamente.
2. **Acesso à Cédula:**
   * Interface responsiva, limpa e otimizada para smartphones, tablets ou navegadores de computador.
   * Não requer instalação de aplicativos nem logins adicionais, garantindo agilidade mesmo em redes móveis (3G/4G/5G).
3. **Momento do Voto:**
   * Se nenhuma votação estiver aberta pela mesa, o eleitor visualiza a mensagem informando que a eleição está fechada ou aguardando início.
   * Assim que a mesa aciona **Iniciar**, a cédula atualiza-se exibindo a pergunta e as opções selecionáveis (botões de opção / rádio).
   * Ao confirmar o voto, recebe retorno visual imediato (*"Resposta aceita"*).
4. **Garantia contra Votos Duplos:**
   * O eleitor pode alterar sua escolha enquanto a votação permanecer aberta na mesa; o sistema sobrescreve e contabiliza apenas o último voto submetido.
   * No momento em que a mesa encerra ou pausa a votação, novos envios são imediatamente bloqueados.

---

### 2.4. Visão do Telão / Plenária (`/painel`)

O **Painel da Assembleia** é projetado no telão da sala ou compartilhado via transmissão de vídeo (Google Meet/Zoom) para que toda a assembleia acompanhe os trabalhos.

#### Modos de Exibição:
1. **Em Exibição:** Projeta o título da questão e as alternativas para leitura coletiva antes da abertura da urna.
2. **Em Votação:** Exibe o aviso de votação aberta e um contador numérico com a quantidade de votos já recebidos em tempo real (sem revelar preferências parciais, protegendo o sigilo).
3. **Resultado / Apuração:**
   * Em votações fechadas: gráfico de barras com total de votos absolutos e porcentagem de cada alternativa, além do total de votantes.
   * Em votações abertas: além dos totais consolidados, lista os nomes de cada votante ao lado de sua respectiva escolha para registro transparente e transcrição em ata.
4. **Sem Flickering:** Atualização assíncrona que redesenha apenas os dados alterados, sem piscar a tela ou recarregar a página inteira.

---

### 2.5. Visão da Administração do Sistema (`/admin`)

Destinada aos administradores de TI responsáveis pelo suporte institucional da plataforma:

* **Auditoria de Logs:** Registro cronológico de todas as operações críticas (disparos de e-mails, alterações de estado, criação de votações, erros de banco).
* **Fila de E-mails:** Monitoramento de mensagens em fila, com filtros de busca, reenvio e visualização do conteúdo HTML enviado aos eleitores.
* **Usuários:** Lista de usuários que já acessaram a plataforma com seus dados funcionais e data do último login.
* **Controles:** Consulta didática às regras da máquina de estados e ações parametrizadas no banco de dados.

---

## 3. Perguntas Frequentes (FAQ)

### P: Quem tem acesso aos tokens dos eleitores?
**R:** O sistema adota o princípio de transparência com auditoria. Na criação da sessão é cadastrado um **e-mail de retorno institucional**. Quando as credenciais são enviadas aos eleitores por e-mail, uma cópia com a lista de tokens é remetida a esse e-mail de retorno para fins de arquivamento pela secretaria do colegiado. Além disso, os gerentes autorizados têm acesso à emissão das papeletas impressas em PDF para votações presenciais.

---

### P: O voto secreto (fechado) é realmente anônimo?
**R:** Sim. Nas votações fechadas, a tabela de respostas armazena exclusivamente a opção escolhida vinculada a uma chave anônima da votação, sem associação ao número USP, nome ou e-mail do eleitor. Não é possível correlacionar no banco de dados qual votante depositou determinada opção.

---

### P: O que acontece se a conexão do eleitor cair durante a votação?
**R:** O link do token continua válido. O eleitor pode reconectar-se de qualquer outro navegador ou dispositivo e submeter seu voto, desde que a mesa diretora ainda não tenha encerrado aquela votação específica.

---

### P: Um eleitor pode votar mais de uma vez?
**R:** Não. Cada eleitor possui um único token válido por votação. Se o eleitor submeter nova opção enquanto a urna estiver aberta, o sistema atualiza o registro existente com a nova escolha (mantendo apenas o voto mais recente). Ao término da votação, cada participante soma exatamente um voto.

---

### P: Como funciona o sistema em reuniões híbridas (parte presencial e parte online)?
**R:** A plataforma atende perfeitamente ao formato híbrido. A secretaria pode enviar os links por e-mail para os participantes remotos e imprimir as papeletas com QR Code para os participantes presentes na sala física. Ambos votam simultaneamente no mesmo pleito e o telão computa a totalização unificada em tempo real.

---

### P: O que fazer se um eleitor não receber ou perder o e-mail do token?
**R:** O gerente da sessão pode acessar a aba **Eleitores**, copiar o link individual de votação daquele participante e reenviá-lo diretamente via chat privado da reunião (ex: chat do Google Meet) ou WhatsApp institucional.

---

### P: A mesa pode criar votações que não foram pautadas antes da reunião?
**R:** Sim. Por meio do botão **Criar votação instantânea** no painel de apoio, a mesa pode cadastrar em poucos segundos uma questão surgida no debate (ex: prorrogação de prazo, pedido de vista ou aprovação de moção), abrindo-a imediatamente para os eleitores sem necessidade de entrar na tela de gerência.
