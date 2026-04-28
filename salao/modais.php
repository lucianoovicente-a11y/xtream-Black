<div class="modal fade" id="modalAgendamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $cs): ?>
                                <option value="<?= $cs['id'] ?>"><?= htmlspecialchars($cs['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serviço</label>
                        <select name="servico_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($servicos as $ss): ?>
                                <option value="<?= $ss['id'] ?>"><?= htmlspecialchars($ss['nome']) ?> - R$ <?= number_format($ss['preco'], 2, ',', '.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e Hora</label>
                        <input type="datetime-local" name="data_hora" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="obs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="salvar_agendamento" class="btn btn-primary">Agendar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarAgendamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_agendamento_id">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" id="edit_agendamento_cliente" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $cs): ?>
                                <option value="<?= $cs['id'] ?>"><?= htmlspecialchars($cs['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serviço</label>
                        <select name="servico_id" id="edit_agendamento_servico" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($servicos as $ss): ?>
                                <option value="<?= $ss['id'] ?>"><?= htmlspecialchars($ss['nome']) ?> - R$ <?= number_format($ss['preco'], 2, ',', '.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e Hora</label>
                        <input type="datetime-local" name="data_hora" id="edit_agendamento_data" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_agendamento_status" class="form-select">
                            <option value="agendado">Agendado</option>
                            <option value="concluido">Concluído</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor Pago (R$)</label>
                        <input type="number" name="valor_pago" id="edit_agendamento_valor" step="0.01" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="obs" id="edit_agendamento_obs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editar_agendamento" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="obs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="salvar_cliente" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_cliente_id">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="edit_cliente_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="edit_cliente_telefone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" id="edit_cliente_whatsapp" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_cliente_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="obs" id="edit_cliente_obs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editar_cliente" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalServico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Serviço</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preço (R$)</label>
                        <input type="number" name="preco" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duração (minutos)</label>
                        <input type="number" name="duracao" value="30" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="salvar_servico" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarServico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_servico_id">
                    <div class="mb-3">
                        <label class="form-label">Nome do Serviço</label>
                        <input type="text" name="nome" id="edit_servico_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preço (R$)</label>
                        <input type="number" name="preco" id="edit_servico_preco" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duração (minutos)</label>
                        <input type="number" name="duracao" id="edit_servico_duracao" value="30" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" id="edit_servico_descricao" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editar_servico" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDespesa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Despesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" name="valor" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="categoria" class="form-select">
                            <option value="produtos">Produtos</option>
                            <option value="aluguel">Aluguel</option>
                            <option value="luz">Luz</option>
                            <option value="agua">Água</option>
                            <option value="internet">Internet</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="salvar_despesa" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarDespesa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Despesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_despesa_id">
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" id="edit_despesa_descricao" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" name="valor" id="edit_despesa_valor" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="categoria" id="edit_despesa_categoria" class="form-select">
                            <option value="produtos">Produtos</option>
                            <option value="aluguel">Aluguel</option>
                            <option value="luz">Luz</option>
                            <option value="agua">Água</option>
                            <option value="internet">Internet</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" id="edit_despesa_data" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editar_despesa" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCobranca" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Cobrança</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $cc): ?>
                                <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" name="valor" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" name="data_vencimento" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="salvar_cobranca" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCobranca" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cobrança</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_cobranca_id">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" id="edit_cobranca_cliente" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $cc): ?>
                                <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" name="valor" id="edit_cobranca_valor" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" id="edit_cobranca_descricao" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" name="data_vencimento" id="edit_cobranca_vencimento" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_cobranca_status" class="form-select">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Pagamento</label>
                        <input type="date" name="data_pagamento" id="edit_cobranca_pagamento" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editar_cobranca" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarAgendamento(id, cliente_id, servico_id, data_hora, status, valor_pago, obs) {
    document.getElementById('edit_agendamento_id').value = id;
    document.getElementById('edit_agendamento_cliente').value = cliente_id;
    document.getElementById('edit_agendamento_servico').value = servico_id;
    document.getElementById('edit_agendamento_data').value = data_hora;
    document.getElementById('edit_agendamento_status').value = status;
    document.getElementById('edit_agendamento_valor').value = valor_pago;
    document.getElementById('edit_agendamento_obs').value = obs || '';
    new bootstrap.Modal(document.getElementById('modalEditarAgendamento')).show();
}

function editarCliente(id, nome, telefone, whatsapp, email, obs) {
    document.getElementById('edit_cliente_id').value = id;
    document.getElementById('edit_cliente_nome').value = nome;
    document.getElementById('edit_cliente_telefone').value = telefone || '';
    document.getElementById('edit_cliente_whatsapp').value = whatsapp || '';
    document.getElementById('edit_cliente_email').value = email || '';
    document.getElementById('edit_cliente_obs').value = obs || '';
    new bootstrap.Modal(document.getElementById('modalEditarCliente')).show();
}

function editarServico(id, nome, preco, duracao, descricao) {
    document.getElementById('edit_servico_id').value = id;
    document.getElementById('edit_servico_nome').value = nome;
    document.getElementById('edit_servico_preco').value = preco;
    document.getElementById('edit_servico_duracao').value = duracao;
    document.getElementById('edit_servico_descricao').value = descricao || '';
    new bootstrap.Modal(document.getElementById('modalEditarServico')).show();
}

function editarDespesa(id, descricao, valor, categoria, data) {
    document.getElementById('edit_despesa_id').value = id;
    document.getElementById('edit_despesa_descricao').value = descricao;
    document.getElementById('edit_despesa_valor').value = valor;
    document.getElementById('edit_despesa_categoria').value = categoria;
    document.getElementById('edit_despesa_data').value = data;
    new bootstrap.Modal(document.getElementById('modalEditarDespesa')).show();
}

function editarCobranca(id, cliente_id, valor, descricao, data_vencimento, status, data_pagamento) {
    document.getElementById('edit_cobranca_id').value = id;
    document.getElementById('edit_cobranca_cliente').value = cliente_id;
    document.getElementById('edit_cobranca_valor').value = valor;
    document.getElementById('edit_cobranca_descricao').value = descricao || '';
    document.getElementById('edit_cobranca_vencimento').value = data_vencimento;
    document.getElementById('edit_cobranca_status').value = status;
    document.getElementById('edit_cobranca_pagamento').value = data_pagamento || '';
    new bootstrap.Modal(document.getElementById('modalEditarCobranca')).show();
}
</script>
