# Git Instructions - Branch: se-tem-validacao-pendente

## 📋 Resumo das Alterações

Implementação da funcionalidade `is_ticketlinked_mandatory` para verificar problemas e mudanças vinculados em aberto antes de permitir o encerramento de um ticket.

## 🔧 Arquivos Modificados

### 1. `/inc/itilsolution.class.php`
- Adicionada verificação de problemas vinculados em aberto (tabela `glpi_problems_tickets`)
- Adicionada verificação de mudanças vinculadas em aberto (tabela `glpi_changes_tickets`)
- Bloqueia solução do ticket se houver problemas ou mudanças que NÃO estejam resolvidos (status != 5) ou fechados (status != 6)

### 2. `/inc/common.class.php`
- Adicionados warnings visuais para problemas vinculados ao ticket
- Adicionados warnings visuais para mudanças vinculadas ao ticket
- Exibe alertas antes do usuário tentar resolver o ticket

### 3. `/locales/pt_BR.po`
- Adicionadas traduções em português brasileiro para as novas mensagens de erro

### 4. `/locales/glpi.pot`
- Atualizadas strings base de tradução com as novas mensagens

## 📝 Mensagens Adicionadas

**Inglês:**
- "You cannot solve/close a ticket with open linked problems"
- "You cannot solve/close a ticket with open linked changes"

**Português (Brasil):**
- "Você não pode solucionar/fechar um chamado com problemas vinculados em aberto"
- "Você não pode solucionar/fechar um chamado com mudanças vinculadas em aberto"

## 🚀 Comandos Git

### Verificar status e diferenças
```bash
# Ver arquivos modificados
git status

# Ver diferenças detalhadas
git diff

# Ver diferenças de um arquivo específico
git diff inc/itilsolution.class.php
git diff inc/common.class.php
```

### Preparar commit
```bash
# Adicionar arquivos modificados
git add inc/itilsolution.class.php
git add inc/common.class.php
git add locales/pt_BR.po
git add locales/glpi.pot

# Ou adicionar todos de uma vez
git add inc/itilsolution.class.php inc/common.class.php locales/pt_BR.po locales/glpi.pot
```

### Criar commit
```bash
# Commit com mensagem descritiva
git commit -m "feat: adicionar verificação de problemas e mudanças vinculados ao ticket

- Implementar verificação is_ticketlinked_mandatory para problemas
- Implementar verificação is_ticketlinked_mandatory para mudanças  
- Bloquear encerramento de ticket com problemas/mudanças em aberto
- Adicionar warnings visuais na interface
- Adicionar traduções pt_BR para novas mensagens"
```

### Enviar para repositório remoto
```bash
# Push da branch atual
git push origin se-tem-validacao-pendente

# Se for o primeiro push da branch
git push -u origin se-tem-validacao-pendente
```

### Criar Pull Request (após push)
1. Acessar GitHub: https://github.com/Joao-SouzaDev/behaviors
2. Clicar em "Compare & pull request" para a branch `se-tem-validacao-pendente`
3. Preencher título: "Adicionar verificação de problemas e mudanças vinculados ao ticket"
4. Adicionar descrição detalhada (usar template abaixo)

## 📄 Template de Pull Request

```markdown
## Descrição
Implementação da funcionalidade `is_ticketlinked_mandatory` para verificar se existem problemas ou mudanças vinculados em aberto antes de permitir o encerramento de um ticket.

## Tipo de Mudança
- [x] Nova funcionalidade (non-breaking change que adiciona funcionalidade)
- [ ] Correção de bug (non-breaking change que corrige um problema)
- [ ] Breaking change (fix ou feature que causa mudança em funcionalidade existente)

## Mudanças Implementadas
- ✅ Verificação de problemas vinculados em aberto (tabela `glpi_problems_tickets`)
- ✅ Verificação de mudanças vinculadas em aberto (tabela `glpi_changes_tickets`)
- ✅ Bloqueio de solução do ticket quando há problemas/mudanças não resolvidos/fechados
- ✅ Warnings visuais na interface antes de tentar resolver ticket
- ✅ Traduções em português brasileiro

## Como Testar
1. Criar um ticket no GLPI
2. Vincular um problema ao ticket
3. Manter o problema com qualquer status exceto "Resolvido" (5) ou "Fechado" (6)
4. Tentar resolver/fechar o ticket
5. Verificar que aparece mensagem de erro impedindo o encerramento
6. Resolver/fechar o problema vinculado
7. Tentar novamente resolver o ticket - deve permitir
8. Repetir teste vinculando uma mudança ao invés de um problema

## Checklist
- [x] Código segue as convenções do projeto
- [x] Comentários adicionados em áreas complexas
- [x] Traduções atualizadas (pt_BR e glpi.pot)
- [x] Testes manuais realizados
- [ ] Documentação atualizada (se necessário)

## Screenshots
(Adicionar prints da mensagem de erro e warnings visuais)

## Relacionado
Issue/Feature Request: #(número, se houver)
```

## 🔄 Comandos Úteis Adicionais

### Ver histórico de commits
```bash
git log --oneline
git log --graph --oneline --all
```

### Desfazer mudanças (se necessário)
```bash
# Desfazer mudanças não commitadas em um arquivo
git checkout -- inc/itilsolution.class.php

# Desfazer último commit (mantém alterações)
git reset --soft HEAD~1

# Desfazer último commit (descarta alterações)
git reset --hard HEAD~1
```

### Atualizar branch com main/master
```bash
# Buscar atualizações do remoto
git fetch origin

# Fazer merge da branch principal
git merge origin/main
# ou
git merge origin/master

# Ou fazer rebase (recomendado para manter histórico limpo)
git rebase origin/main
```

## 📌 Notas Importantes

1. **Tabelas do GLPI utilizadas:**
   - `glpi_problems_tickets`: relacionamento direto entre problemas e tickets
   - `glpi_changes_tickets`: relacionamento direto entre mudanças e tickets
   - Ambas usam `tickets_id` como chave para buscar relacionamentos

2. **Lógica de Status:**
   - Bloqueia encerramento se problema/mudança **NÃO** estiver com status 5 (Resolvido) **OU** 6 (Fechado)
   - Permite encerramento apenas quando todos os problemas/mudanças vinculados estão resolvidos ou fechados
   - Qualquer outro status (1=Novo, 2=Em atendimento, 3=Planejado, 4=Pendente, etc.) bloqueia

3. **Parâmetro de configuração:**
   - `is_ticketlinked_mandatory` (já existente no banco de dados)
   - Configurável em: Setup > Behaviours

## 🆘 Troubleshooting

Se houver conflitos no merge:
```bash
# Ver arquivos com conflito
git status

# Editar arquivos e resolver conflitos manualmente
# Após resolver:
git add <arquivo-resolvido>
git commit -m "Resolver conflitos de merge"
```

---
**Data:** 30/11/2025  
**Branch:** se-tem-validacao-pendente  
**Autor:** Copilot + Joao-SouzaDev
