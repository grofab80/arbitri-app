<?php
    include '../admin/includes/topbar.php';
?>

<section class="content container-fluid">
    <div class="row import-header">
        <div class="col-sm-5 header-item">
            <h3>Sincronizzazione WordPress</h3>
        </div>
        <div class="col-sm-7 header-item header-actions">
            <button type="button" id="testSyncConnection" class="btn btn-default btn-sm" data-permission="import.manage">
                <i class="fa fa-plug"></i> Verifica connessione
            </button>
            <button type="button" id="runFullSync" class="btn btn-default btn-sm" data-permission="import.run">
                <i class="fa fa-refresh"></i> Sync completo
            </button>
            <button type="button" id="runIncrementalSync" class="btn btn-primary btn-sm" data-permission="import.run">
                <i class="fa fa-play"></i> Sync incrementale
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Sorgente dati</h3>
                    <span id="syncSourceStatus" class="label label-default pull-right">Non configurata</span>
                </div>
                <form id="syncSourceForm" novalidate>
                    <div class="box-body">
                        <div id="wrapSyncName" class="form-group">
                            <label for="syncSourceName">Nome</label>
                            <input type="text" id="syncSourceName" class="form-control" maxlength="100">
                            <div class="error-msg"></div>
                        </div>
                        <div id="wrapSyncBaseUrl" class="form-group">
                            <label for="syncBaseUrl">URL sito WordPress</label>
                            <input type="url" id="syncBaseUrl" class="form-control" placeholder="https://www.esempio.it">
                            <p class="help-block">Puoi indicare il sito oppure la base REST completa del plugin.</p>
                            <div class="error-msg"></div>
                        </div>
                        <div id="wrapSyncApiKey" class="form-group">
                            <label for="syncApiKey">Chiave API</label>
                            <input type="password" id="syncApiKey" class="form-control" autocomplete="new-password">
                            <p id="syncApiKeyHint" class="help-block">La chiave viene cifrata prima del salvataggio.</p>
                            <div class="error-msg"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div id="wrapSyncTimeout" class="form-group">
                                    <label for="syncTimeout">Timeout richiesta</label>
                                    <div class="input-group">
                                        <input type="number" id="syncTimeout" class="form-control" min="5" max="120" value="20">
                                        <span class="input-group-addon">sec</span>
                                    </div>
                                    <div class="error-msg"></div>
                                </div>
                            </div>
                            <div class="col-sm-6 sync-options">
                                <label class="checkbox-inline"><input type="checkbox" id="syncEnabled"> Attiva sincronizzazione</label>
                                <label class="checkbox-inline"><input type="checkbox" id="syncVerifySsl" checked> Verifica SSL</label>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer text-right" data-permission="import.manage">
                        <button type="submit" id="saveSyncSource" class="btn btn-primary btn-sm">
                            <i class="fa fa-save"></i> Salva configurazione
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Stato sincronizzazione</h3>
                    <span id="syncLastStatusBadge" class="label label-default pull-right">Mai eseguita</span>
                </div>
                <div class="box-body">
                    <dl class="dl-horizontal sync-status-list">
                        <dt>Ultimo esito</dt><dd id="syncLastStatus">Mai eseguita</dd>
                        <dt>Ultima esecuzione</dt><dd id="syncLastAt">-</dd>
                        <dt>Dati sincronizzati fino al</dt><dd id="syncLastCursor">-</dd>
                        <dt>Messaggio</dt><dd id="syncLastMessage">-</dd>
                    </dl>
                </div>
            </div>
            <div id="syncProgressPanel" class="sync-progress-panel hidden" aria-live="polite">
                <div class="sync-progress-heading">
                    <div>
                        <i id="syncProgressIcon" class="fa fa-refresh fa-spin"></i>
                        <strong id="syncProgressTitle">Sincronizzazione in corso</strong>
                    </div>
                    <strong id="syncProgressPercentage">Preparazione</strong>
                </div>
                <div id="syncProgressTrack" class="progress active">
                    <div id="syncProgressBar"
                         class="progress-bar progress-bar-info progress-bar-striped"
                         role="progressbar"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         aria-valuenow="0"></div>
                </div>
                <div class="sync-progress-meta">
                    <span><i class="fa fa-tasks"></i> <span id="syncProgressPhase">Preparazione indice remoto</span></span>
                    <span><i class="fa fa-list-ol"></i> <span id="syncProgressCount">Totale in calcolo</span></span>
                    <span><i class="fa fa-clock-o"></i> <span id="syncProgressElapsed">00:00</span></span>
                </div>
            </div>
            <div class="callout callout-info sync-policy-note">
                <h4><i class="fa fa-shield"></i> Import non distruttivo</h4>
                <p>Le eliminazioni rilevate su WordPress vengono registrate come tombstone. I record locali non vengono cancellati automaticamente.</p>
            </div>
        </div>
    </div>

    <div class="box box-primary sync-overrides-box">
        <div class="box-header with-border">
            <h3 class="box-title">Correzioni mapping</h3>
            <span id="syncOverrideCount" class="label label-default pull-right">0 anomalie</span>
        </div>
        <div class="box-body">
            <div class="row sync-overrides-toolbar" data-permission="import.manage">
                <div class="col-sm-3">
                    <label for="bulkSyncFootballType">Disciplina selezionati</label>
                    <select id="bulkSyncFootballType" class="form-control input-sm">
                        <option value="">Non modificare</option>
                        <option value="11">Calcio a 11</option>
                        <option value="7">Calcio a 7</option>
                        <option value="5">Calcio a 5</option>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label for="bulkSyncSeason">Stagione selezionati</label>
                    <select id="bulkSyncSeason" class="form-control input-sm">
                        <option value="">Non modificare</option>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label for="bulkSyncIgnored">Gestione selezionati</label>
                    <select id="bulkSyncIgnored" class="form-control input-sm">
                        <option value="">Non modificare</option>
                        <option value="1">Ignora nello sync</option>
                        <option value="0">Includi nello sync</option>
                    </select>
                </div>
                <div class="col-sm-3 sync-overrides-actions">
                    <button type="button" id="applySyncOverrides" class="btn btn-default btn-sm">
                        <i class="fa fa-check-square-o"></i> Applica selezione
                    </button>
                    <button type="button" id="saveSyncOverrides" class="btn btn-primary btn-sm">
                        <i class="fa fa-save"></i> Salva
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="syncOverrides" class="table table-bordered table-striped">
                    <thead><tr>
                        <th><input type="checkbox" id="selectAllSyncOverrides" title="Seleziona tutti"></th>
                        <th>Entita</th><th>ID esterno</th><th>Nome</th><th>Lega / origine</th>
                        <th>Problema</th><th>Disciplina</th><th>Stagione locale</th><th>Ignora</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <p id="syncOverridesEmpty" class="text-muted text-center hidden">Nessuna correzione richiesta.</p>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Storico esecuzioni</h3></div>
        <div class="box-body">
            <table id="syncRuns" class="table table-bordered table-striped">
                <thead><tr>
                    <th>Avvio</th><th>Modalita</th><th>Stato</th><th>Creati</th><th>Aggiornati</th>
                    <th>Invariati</th><th>Ignorati</th><th>Errori</th><th>Tombstone</th><th>Utente</th><th>Azioni</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="syncRunItemsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-list"></i> Dettaglio sincronizzazione</h4>
            </div>
            <div class="modal-body">
                <table id="syncRunItems" class="table table-bordered table-striped">
                    <thead><tr><th>Entita</th><th>ID esterno</th><th>ID locale</th><th>Esito</th><th>Messaggio</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button></div>
        </div>
    </div>
</div>

<script src="js/import.js?v=<?php echo filemtime(__DIR__ . '/js/import.js'); ?>"></script>

<?php include '../admin/includes/footer.php'; ?>
