<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>
<% if $CSVFile %>
<div class="table-responsive">
    $renderCSVasTable
</div>
<% end_if %>
