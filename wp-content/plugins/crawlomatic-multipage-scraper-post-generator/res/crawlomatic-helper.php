<?php
   function crawlomatic_helper()
   {
       $crawlomatic_Main_Settings = get_option('crawlomatic_Main_Settings', false);
       $crawled_content = '';
       if(isset($_POST['crawlomatic_crawl']) && isset($_POST['crawlomatic_crawl_url']))
       {
           if (filter_var($_POST['crawlomatic_crawl_url'], FILTER_VALIDATE_URL)) {
               if(isset($_POST['crawlomatic_use_phantomjs_help']) && $_POST['crawlomatic_use_phantomjs_help'] != '')
               {
                     $got_phantom = false;
                     $use_phantom = $_POST['crawlomatic_use_phantomjs_help'];
                     if($use_phantom == '1')
                     {
                        $crawled_content = crawlomatic_get_page_PhantomJS($_POST['crawlomatic_crawl_url'], '', '', '1', '', '2000', '', '', '');
                        if($crawled_content !== false)
                        {
                              $got_phantom = true;
                        }
                     }
                     elseif($use_phantom == '2')
                     {
                        $crawled_content = crawlomatic_get_page_Puppeteer($_POST['crawlomatic_crawl_url'], '', '', '1', '', '2000', '', '', '');
                        if($crawled_content !== false)
                        {
                              $got_phantom = true;
                        }
                     }
                     elseif($use_phantom == '3')
                     {
                        $crawled_content = crawlomatic_get_page_Tor($_POST['crawlomatic_crawl_url'], '', '', '1', '', '2000', '', '', '');
                        if($crawled_content !== false)
                        {
                              $got_phantom = true;
                        }
                     }
                     elseif($use_phantom == '4')
                     {
                        $crawled_content = crawlomatic_get_page_PuppeteerAPI($_POST['crawlomatic_crawl_url'], '', '', '1', '', '2000', '', '', '', '', '', '');
                        if($crawled_content !== false)
                        {
                              $got_phantom = true;
                        }
                     }
                     elseif($use_phantom == '5')
                     {
                        $crawled_content = crawlomatic_get_page_TorAPI($_POST['crawlomatic_crawl_url'], '', '', '1', '', '2000', '', '', '', '', '', '');
                        if($crawled_content !== false)
                        {
                              $got_phantom = true;
                        }
                     }
                     elseif($use_phantom == '6')
                     {
                        $crawled_content = crawlomatic_get_page_PhantomJSAPI($_POST['crawlomatic_crawl_url'], '', '', '1', '', '2000', '', '', '');
                        if($crawled_content !== false)
                        {
                              $got_phantom = true;
                        }
                     }
               }
               if($got_phantom === false)
               {
                  $crawled_content = crawlomatic_get_web_page($_POST['crawlomatic_crawl_url'], '', '', '1', '', '', '', '');
               }
               if($crawled_content === false || $crawled_content === '')
               {
                     $crawled_content = esc_html__('Error in page crawling. Please try crawling the page with different settings. Also, please check if PhantomJS/Puppeteer is properly configured on your server.', 'crawlomatic-multipage-scraper-post-generator');
               }
               else
               {
                   $crawled_content = crawlomatic_get_web_page($_POST['crawlomatic_crawl_url'], '', '', '1', '', '', '', '');
                   if($crawled_content === false || $crawled_content === '')
                   {
                       $crawled_content = esc_html__('Error in page crawling. Please try again/other webpage.', 'crawlomatic-multipage-scraper-post-generator');
                   }
               }
           }
           else
           {
               $crawled_content = esc_html__('Invalid URL provided: ', 'crawlomatic-multipage-scraper-post-generator') . esc_url_raw($_POST['crawlomatic_crawl_url']);
           }
       }
   ?>
<div class="wp-header-end"></div>
<div class="wrap gs_popuptype_holder seo_pops">
   <div>
      <div>
         <div>
            <form method="post" onsubmit="return confirm('Are you sure you want to crawl this webpage?');">
               <h3>
                  <?php echo esc_html__("URL To Get Info From:", 'crawlomatic-multipage-scraper-post-generator');?>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Input the URL you want to get HTML Class, ID or XPATH from.", 'crawlomatic-multipage-scraper-post-generator');
                           ?>
                     </div>
                  </div>
               </h3>
               <br/>
               <?php echo esc_html__("Use PhantomJS:", 'crawlomatic-multipage-scraper-post-generator');?>
               <select id="crawlomatic_use_phantomjs_help" name="crawlomatic_use_phantomjs_help" class="cr_width_full">
                  <option value="0" selected><?php echo esc_html__("WordPress (Default)", 'crawlomatic-multipage-scraper-post-generator');?></option>
                  <option value="1"><?php echo esc_html__("PhantomJS (needs to be installed on server)", 'crawlomatic-multipage-scraper-post-generator');?></option>          
                  <option value="2"><?php echo esc_html__("Puppeteer (needs to be installed on server)", 'crawlomatic-multipage-scraper-post-generator');?></option>
                  <option value="3"><?php echo esc_html__("Tor (needs to be installed on server)", 'crawlomatic-multipage-scraper-post-generator');?></option>
                  <option value="4"<?php if (!isset($crawlomatic_Main_Settings['headlessbrowserapi_key']) || trim($crawlomatic_Main_Settings['headlessbrowserapi_key']) == ''){echo ' title="' . esc_html__("This option is disabled. To enable it, add a HeadlessBrowserAPI Key in the plugin's 'Main Settings' menu.", 'crawlomatic-multipage-scraper-post-generator') . '" disabled';}?>><?php echo esc_html__("Puppeteer (HeadlessBrowserAPI)", 'crawlomatic-multipage-scraper-post-generator');if (!isset($crawlomatic_Main_Settings['headlessbrowserapi_key']) || trim($crawlomatic_Main_Settings['headlessbrowserapi_key']) == ''){echo esc_html__(' - to enable, add a HeadlessBrowserAPI key in the plugin\'s \'Main Settings\'', 'crawlomatic-multipage-scraper-post-generator');}?></option>
                  <option value="5"<?php if (!isset($crawlomatic_Main_Settings['headlessbrowserapi_key']) || trim($crawlomatic_Main_Settings['headlessbrowserapi_key']) == ''){echo ' title="' . esc_html__("This option is disabled. To enable it, add a HeadlessBrowserAPI Key in the plugin's 'Main Settings' menu.", 'crawlomatic-multipage-scraper-post-generator') . '" disabled';}?>><?php echo esc_html__("Tor (HeadlessBrowserAPI)", 'crawlomatic-multipage-scraper-post-generator');if (!isset($crawlomatic_Main_Settings['headlessbrowserapi_key']) || trim($crawlomatic_Main_Settings['headlessbrowserapi_key']) == ''){echo esc_html__(' - to enable, add a HeadlessBrowserAPI key in the plugin\'s \'Main Settings\'', 'crawlomatic-multipage-scraper-post-generator');}?></option>
                  <option value="6"<?php if (!isset($crawlomatic_Main_Settings['headlessbrowserapi_key']) || trim($crawlomatic_Main_Settings['headlessbrowserapi_key']) == ''){echo ' title="' . esc_html__("This option is disabled. To enable it, add a HeadlessBrowserAPI Key in the plugin's 'Main Settings' menu.", 'crawlomatic-multipage-scraper-post-generator') . '" disabled';}?>><?php echo esc_html__("PhantomJS (HeadlessBrowserAPI)", 'crawlomatic-multipage-scraper-post-generator');if (!isset($crawlomatic_Main_Settings['headlessbrowserapi_key']) || trim($crawlomatic_Main_Settings['headlessbrowserapi_key']) == ''){echo esc_html__(' - to enable, add a HeadlessBrowserAPI key in the plugin\'s \'Main Settings\'', 'crawlomatic-multipage-scraper-post-generator');}?></option>
               </select> 
                  <br/><br/>
               <input name="crawlomatic_crawl_url" type="url" validator="url" placeholder="URL to crawl" class="cr_width_full" value="<?php
                     if(isset($_POST['crawlomatic_crawl_url']))
                     {
                         echo esc_url_raw($_POST['crawlomatic_crawl_url']);
                     }
                     if(isset($_POST['crawlomatic_crawl_type']))
                     {
                         $crawlomatic_crawl_type = $_POST['crawlomatic_crawl_type'];
                     }
                     else
                     {
                         $crawlomatic_crawl_type = '';
                     }
                     ?>"><br/><br/>
               <input name="crawlomatic_crawl" type="submit" title="Submit for crawl" value="Crawl" class="cr_width_full">
               <br/><br/>
               <hr/>
               <h3>
                  <?php echo esc_html__("Query Type:", 'crawlomatic-multipage-scraper-post-generator');?>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Input the query type that you want to apply for the crawled article. This should be the same as the 'Query Type' settings field from the plugin settings.", 'crawlomatic-multipage-scraper-post-generator');
                           ?>
                     </div>
                  </div>
               </h3>
               <select name="crawlomatic_crawl_type" id="crawlomatic_crawl_type" class="cr_width_full">
                  <option value="class"<?php if($crawlomatic_crawl_type == 'class') echo ' selected';?>
                     ><?php echo esc_html__("Get Clicked Element HTML Class", 'crawlomatic-multipage-scraper-post-generator');?></option>
                  <option value="id"<?php if($crawlomatic_crawl_type == 'id') echo ' selected';?>
                     ><?php echo esc_html__("Get Clicked Element HTML ID", 'crawlomatic-multipage-scraper-post-generator');?></option>
                  <option value="xpath"<?php if($crawlomatic_crawl_type == 'xpath') echo ' selected';?>
                     ><?php echo esc_html__("Get Clicked Element XPATH Expression", 'crawlomatic-multipage-scraper-post-generator');?></option>
               </select>
               <br/>
            </form>
         </div>
         <hr/>
         <?php
            if(isset($_POST['crawlomatic_crawl_url']))
            {
            ?>
         <h3>
            <?php echo esc_html__("Crawled Content:", 'crawlomatic-multipage-scraper-post-generator');?>
            <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
               <div class="bws_hidden_help_text cr_min_260px">
                  <?php
                     echo esc_html__("Here you can see the crawled webpage content.", 'crawlomatic-multipage-scraper-post-generator');
                     ?>
               </div>
            </div>
         </h3>
         <br/>
         <div id="crawlomatic_container" class="cr_helper">
            <?php
               $parsedUrl = parse_url($_POST['crawlomatic_crawl_url']);
               $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
               $crawled_content = preg_replace('{=["\']/(\w)["\']}', '="' . esc_url_raw($root) . '/$1"', $crawled_content);
               echo $crawled_content;
               ?>
         </div>
         <hr/>
         <?php
            }
            ?>
      </div>
   </div>
</div>
<?php
   }
   ?>