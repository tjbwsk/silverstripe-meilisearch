<% include SideBar %>
<div class="content-container unit size3of4 lastUnit">
    <article>
        <h1>$Title</h1>
        <div class="content">{$Content}</div>
    </article>
    {$Form}

    <% if $IsSearch %>
        <h2><%t BimTheBam\Meilisearch\Model\CMS\SearchPage.SEARCH_RESULTS 'Search results' %></h2>

        <% if $Form.ResultsByIndex %>
            <% loop $Form.ResultsByIndex %>
                <% if $Up.ResultsByIndex.count > 1 %><h3 id="i-{$IndexUniqueKey}">{$IndexName}</h3><% end_if %>
                <% if $Results.Count > 0 %>
                    <% if $Results.OverLimit %><p><small>More than {$Results.Limit} results. Please refine your search.</small></p><% end_if %>
                    <% loop $Results.getList(true, 10) %>
                        <% if $Record.Title %><h4>{$Record.Title}</h4><% end_if %>
                        <% if $Record.Content %><p>{$Record.Content.ContextSummary(200, $Up.Up.Up.Q)}</p><% end_if %>
                        <% if $Record.Link %><p><a href="{$Record.Link}" title="<%t BimTheBam\Meilisearch\Model\CMS\SearchPage.TO_CONTENT 'To content' %>"><%t BimTheBam\Meilisearch\Model\CMS\SearchPage.TO_CONTENT 'To content' %></a></p><% end_if %>
                    <% end_loop %>
                    <% if $Results.getList(true, 10).MoreThanOnePage %>
                        <% with $Results.getList(true, 10) %>
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item">
                                        <a class="page-link<% if $FirstPage %> disabled<% end_if %>" href="{$PrevLink}#i-{$Up.Up.IndexUniqueKey}">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <% loop $PaginationSummary(5) %>
                                        <li class="page-item<% if $CurrentBool %> active<% end_if %>">
                                            <% if $Link %>
                                                <a href="{$Link}#i-{$Up.Up.IndexUniqueKey}" class="page-link">{$PageNum}</a>
                                            <% else %>
                                                <span class="page-link disabled">...</span>
                                            <% end_if %>
                                        </li>
                                    <% end_loop %>
                                    <li class="page-item">
                                        <a class="page-link<% if $LastPage %> disabled<% end_if %>" href="{$NextLink}#i-{$Up.Up.IndexUniqueKey}">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <% end_with %>
                    <% end_if %>
                <% else %>
                    <p><%t BimTheBam\Meilisearch\Model\CMS\SearchPage.NO_RESULTS 'No results.' %></p>
                <% end_if %>
            <% end_loop %>
        <% end_if %>
    <% end_if %>

    {$CommentsForm}
</div>
