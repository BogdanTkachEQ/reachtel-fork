                                        <div class="pagination">
                                                <div class="left">
                                                        Showing <strong>{$paginate_show|number_format}</strong> of <strong>{$paginate_count|number_format}</strong> records. Page <strong>{$paginate_page|number_format}</strong> of <strong>{$paginate_pages|number_format}</strong>.
                                                </div>

                                                <div class="right">
                                                        <ul>
                                                                <li class="first">{if $paginate_page != 1}<a href="?offset=0{if !empty($search)}&search={$search}{/if}{if !empty($activeonly)}&activeonly=true{/if}"><span class="famfam" alt="First" title="First" style="background-position: -520px -360px;"></span></a>{else}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{/if}</li>
                                                                <li>{if $paginate_page != 1}<a href="?offset={$paginate_offset-$paginate_increment-$paginate_increment}{if !empty($search)}&search={$search}{/if}{if !empty($activeonly)}&activeonly=true{/if}"><span class="famfam" alt="Previous" title="Previous" style="background-position: -580px -360px;"></span></a>{else}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{/if}</li>
                                                                <li>{if $paginate_page != $paginate_pages}<a href="?offset={$paginate_offset}{if !empty($search)}&search={$search|escape:'url'}{/if}{if !empty($activeonly)}&activeonly=true{/if}"><span class="famfam" alt="Next" title="Next" style="background-position: -560px -360px;"></span></a>{else}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{/if}</li>
                                                                <li>{if $paginate_page != $paginate_pages}<a href="?offset={$paginate_last}{if !empty($search)}&search={$search|escape:'url'}{/if}{if !empty($activeonly)}&activeonly=true{/if}"><span class="famfam" alt="Last" title="Last" style="background-position: -540px -360px;"></span></a>{else}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{/if}</li>
                                                        </ul>

                                                </div>
                                                <div class="clear-both"></div>
                                        </div>

