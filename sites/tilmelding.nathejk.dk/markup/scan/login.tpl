{extends file="scan/page.tpl"}
{block name=container}
      <div class="starter-template">
        <h1>Scan</h1>
        <p class="lead">For at kunne scanne patruljer skal du logge ind, det gør du ved at skrive dit telefonnummer i feltet herunder</p>
        <form class="form-inline" action="" method="post">
            <div class="form-group">
                <input type="text" class="form-control" placeholder="telefonnummer" name="phone">
            </div>
            <button type="submit" class="btn btn-default btn-success">Go</button>
        </form>
      </div>
{/block}
