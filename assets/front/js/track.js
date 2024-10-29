(function ($) {
	$(function () {
        const AffiLinks = {

            links: AffiLinksVars.links,
            assets: AffiLinksVars.assets,

            init: function() {
                this.impressions()
                this.bind()
            },

            impressions: function() {
                AffiLinks.link_impressions()
                AffiLinks.asset_impressions()
            },

            bind: function() {
                $( window ).on( 'scroll', this.impressions )
            },

            link_impressions: function() {
                if ( ! this.links ) {
                    return
                }

                let allLinkSelectors = this.links.map( link => `a[href="${link}"]` ).join( ',' )
                const links = $( allLinkSelectors ).not( '.tracked' )
                if ( ! links.length ) {
                    return
                }

                links.each( function( index, item) {
                    const self = $( item )
                    if ( self.hasClass( 'tracked' ) ) {
                        return
                    }

                    if ( ! AffiLinks.inViewport( item ) ) {
                        return
                    }

                    // Avoid repeat visit.
                    self.addClass( 'tracked' )

                    const source_id   = AffiLinksVars.source_id || 0
                    const source_type = AffiLinksVars.source_type || 'direct'
                    const visit_type  = AffiLinksVars.visit_type || 'direct'

                    let link = self.attr( 'href' ) || ''
                    link = link.replace( AffiLinksVars.site_url, '' )

                    $.ajax({
                        url: `${AffiLinksVars.rest_url}/track/impression`,
                        type: 'POST',
                        data: {
                            short_link: link,
                            affiliate_type: 'link',
                            source_id: source_id,
                            source_type: source_type,
                            visit_type: visit_type,
                            nonce: AffiLinksVars.nonce,
                        }
                    });
                });
            },

            asset_impressions: function() {
                if ( ! this.assets ) {
                    return
                }

                let allLinkSelectors = this.assets.map( link => `a[href="${link}"]` ).join( ',' )
                const links = $( allLinkSelectors ).not( '.tracked' )
                if ( ! links.length ) {
                    return
                }

                links.each( function( index, item) {
                    const self = $( item )
                    if ( self.hasClass( 'tracked' ) ) {
                        return
                    }

                    if ( ! AffiLinks.inViewport( item ) ) {
                        return
                    }

                    // Avoid repeat visit.
                    self.addClass( 'tracked' )

                    const source_id = AffiLinksVars.source_id || 0
                    const source_type = AffiLinksVars.source_type || 'direct'
                    const visit_type = AffiLinksVars.visit_type || 'direct'

                    let link = self.attr( 'href' ) || ''
                    link = link.replace( AffiLinksVars.site_url, '' )

                    $.ajax({
                        url: `${AffiLinksVars.rest_url}/track/impression`,
                        type: 'POST',
                        data: {
                            short_link: link,
                            affiliate_type: 'asset',
                            source_id: source_id,
                            source_type: source_type,
                            visit_type: visit_type,
                            nonce: AffiLinksVars.nonce,
                        }
                    });

                });
            },

            inViewport: function ( event ) {
                const rect = event.getBoundingClientRect()
                const windowHeight = window.innerHeight || document.documentElement.clientHeight
                
                return (
                    (rect.top >= 0 && rect.top <= windowHeight) ||
                    (rect.bottom >= 0 && rect.bottom <= windowHeight)
                )
            }
        }

        AffiLinks.init()
    });

})(jQuery);