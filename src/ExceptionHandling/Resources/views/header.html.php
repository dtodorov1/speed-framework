<div class="exception">
  <div class="exc-title">
    <?php foreach ($name as $i => $nameSection): ?>
      <?php if ($i == count($name) - 1): ?>
        <span class="exc-title-primary"><?php echo $tpl->escape($nameSection) ?></span>
      <?php else: ?>
        <?php echo $tpl->escape($nameSection) . ' \\' ?>
      <?php endif ?>
    <?php endforeach ?>
    <?php if ($code): ?>
      <span title="Exception Code">(<?php echo $tpl->escape($code) ?>)</span>
    <?php endif ?>
  </div>

  <div class="exc-message">
    <?php if (!empty($message)): ?>
      <span><?php echo $tpl->escape($message) ?></span>


      <?php if (count($previousMessages)): ?>
        <div class="exc-title prev-exc-title">
          <span class="exc-title-secondary">Previous exceptions</span>
        </div>

        <ul>
          <?php foreach ($previousMessages as $i => $previousMessage): ?>
            <li>
              <?php echo $tpl->escape($previousMessage) ?>
              <span class="prev-exc-code">(<?php echo $previousCodes[$i] ?>)</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif ?>



    <?php else: ?>
      <span class="exc-message-empty-notice">No message</span>
    <?php endif ?>

    <ul class="search-for-help">
      <?php if (!empty($docref_url)): ?>
      <li>
        <a rel="noopener noreferrer" target="_blank" href="<?php echo $docref_url; ?>" title="Search for help in the PHP manual.">
          <!-- PHP icon by Icons Solid -->
          <!-- https://www.iconfinder.com/icons/322421/book_icon -->
          <!-- Free for commercial use -->
          <svg height="16px" id="Layer_1" style="enable-background:new 0 0 32 32;" version="1.1" viewBox="0 0 32 32" width="16px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g transform="translate(240 0)"><path d="M-211,4v26h-24c-1.104,0-2-0.895-2-2s0.896-2,2-2h22V0h-22c-2.209,0-4,1.791-4,4v24c0,2.209,1.791,4,4,4h26V4H-211z    M-235,8V2h20v22h-20V8z M-219,6h-12V4h12V6z M-223,10h-8V8h8V10z M-227,14h-4v-2h4V14z"/></g></svg>
        </a>
      </li>
      <?php endif ?>
      <li>
        <a rel="noopener noreferrer" target="_blank" href="https://google.com/search?q=<?php echo urlencode(implode('\\', $name).' '.$message) ?>" title="Search for help on Google.">
          Google it
        </a>
      </li>,
      <li>
        <a rel="noopener noreferrer" target="_blank" href="https://duckduckgo.com/?q=<?php echo urlencode(implode('\\', $name).' '.$message) ?>" title="Search for help on DuckDuckGo.">
          Duck Duck Go it
        </a>
      </li>,
      <li>
        <a rel="noopener noreferrer" target="_blank" href="https://stackoverflow.com/search?q=<?php echo urlencode(implode('\\', $name).' '.$message) ?>" title="Search for help on Stack Overflow.">
          Stack overflow it
        </a>
      </li>
    </ul>

    <span id="plain-exception"><?php echo $tpl->escape($plain_exception) ?></span>
    <button id="copy-button" class="rightButton clipboard" data-clipboard-text="<?php echo $tpl->escape($plain_exception) ?>" title="Copy exception details to clipboard">
      COPY
    </button>
    <button id="hide-error" class="rightButton" title="Hide error message" onclick="document.getElementsByClassName('Whoops')[0].style.display = 'none';">
      HIDE
    </button>
  </div>
</div>
