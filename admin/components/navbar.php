        <!-- Sidebar fixa em telas grandes -->
        <nav class="col-lg-2 d-none d-lg-block bg-primary sidebar p-3 min-vh-100 border-end position-fixed">
           <div class="text-center mt-3">
              <h4 class="text-white">
                 <img src="../imagens/favicon.ico" alt="Logo" width="30"> Administrador
              </h4>
           </div>
           <hr>
           <ul class="nav flex-column">
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('dashboard.php')"><i class="bi bi-speedometer2 me-2"></i> Dashboard</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('chamados.php')"><i class="bi bi-chat-dots-fill me-2"></i> Chamados</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('saques.php')"><i class="bi bi-cash-coin me-2"></i> Solic. Saques</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('clientes.php')"><i class="bi bi-people-fill me-2"></i> Clientes</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('clientescfg.php')"><i class="bi bi-people-fill me-2"></i> Clientes Acessos</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('verificardocs.php')"><i class="bi bi-folder-check me-2"></i> Verificar Docs</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('relatorios.php')"><i class="bi bi-bar-chart-line-fill me-2"></i> Relatórios</button></li>
              <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('configuracoes.php')"><i class="bi bi-gear-fill me-2"></i> Configurações</button></li>
           </ul>
        </nav>

        <!-- Navbar topo para mobile -->
        <nav class="d-lg-none">
           <div class="d-flex justify-content-between align-items-center p-2 bg-primary text-white border-bottom">
              <button class="btn btn-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav">
                 <i class="bi bi-list"></i>
              </button>
              <h5 class="m-0"><img src="../imagens/favicon.ico" alt="Logo" width="30"> Administrador</h5>
           </div>
        </nav>

        <!-- Offcanvas Menu Mobile -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNav">
           <div class="offcanvas-header">
              <h5 class="offcanvas-title"><img src="../imagens/favicon.ico" alt="Logo" width="30"> Administrador</h5>
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
           </div>
           <div class="offcanvas-body">
              <ul class="nav flex-column">
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('dashboard.php')"><i class="bi bi-speedometer2 me-2"></i> Dashboard</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('chamados.php')"><i class="bi bi-chat-dots-fill me-2"></i> Chamados</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('saques.php')"><i class="bi bi-cash-coin me-2"></i> Solic. Saques</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('clientes.php')"><i class="bi bi-people-fill me-2"></i> Clientes</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('clientescfg.php')"><i class="bi bi-people-fill me-2"></i> Clientes Acessos</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('verificardocs.php')"><i class="bi bi-folder-check me-2"></i> Verificar Docs</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('relatorios.php')"><i class="bi bi-bar-chart-line-fill me-2"></i> Relatórios</button></li>
                 <li class="nav-item mb-2"><button class="btn btn-light w-100 text-start" onclick="carregarConteudo('configuracoes.php')"><i class="bi bi-gear-fill me-2"></i> Configurações</button></li>
              </ul>
           </div>
        </div>