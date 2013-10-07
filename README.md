# AOE ClassPathCache

## Change log

* v0.2.2
    * Revalidate cache rather than clearing cache to prevent stampeding.
    * Check that APC is actually enabled.
    * Do not store apc cache for cli mode.
    * Make useAPC static property public to allow it to be explicitly enabled or disabled.
    * Remove tmp files on failed rename.

* v0.2.1
    * Adding button to cache management page to flush Aoe_ClassPathCache content

* v0.2.0
    * Adding controller that allows clearing apc cache in a frontend context and helper function that abstracts from that/

* v0.1.0
    * Adding APC support

## Usage

### Clean cache (from everywhere - will do internal frontend call if needed to delete APC content)

    Mage::helper('aoe_classpathcache')->clearClassPathCache();

### Command line

    cd shell/
    php aoe_classpathcache.php -action clear
