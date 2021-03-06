<?php

/*
 * This file is part of oui_provider,
 * an extendable plugin to easily embed
 * customizable players in Textpattern CMS.
 *
 * https://github.com/NicolasGraph/oui_provider
 *
 * Copyright (C) 2018 Nicolas Morand
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA..
 */

/**
 * Provider
 *
 * @package Oui\Player
 */

namespace Oui;

abstract class Provider implements \Textpattern\Container\ReusableInterface
{
    /**
     * The provider name (set from the class name).
     *
     * @var string
     * @see setProvider(), getProvider().
     */

    protected static $provider;

    /**
     * The player base path.
     *
     * @var string
     * @see getSrcBase().
     */

    protected static $srcBase;

    /**
     * URL of a script to embed.
     *
     * @var string
     * @example 'https://platform.vine.co/static/scripts/embed.js'
     * @see getScript(), embedScript(), $scriptEmbedded.
     */

    protected static $script;

    /**
     * Whether the script is already embed or not.
     *
     * @var bool
     * @see embedScript(), getScriptEmbedded().
     */

    protected static $scriptEmbedded = false;

    /**
     * Initial player size.
     *
     * @var array
     * @see getIniDims(), getTagAtts().
     */

    protected static $iniDims = array(
        'width'      => '640',
        'height'     => '',
        'ratio'      => '16:9',
        'responsive' => array(
            'default' => 'false',
            'valid'   => array('true', 'false'),
        ),
    );

    /**
     * Current Player size.
     *
     * @var array
     * @see setDims(), getDims().
     */

    protected $dims;

    /**
     * The value provided through the play attribute.
     *
     * @var string
     * @see setMedia(), getMedia().
     */

    protected $media;

    /**
     * The media type provided.
     *
     * @var string
     * @see getMediaType().
     */

    protected static $mediaType = 'video';

    /**
     * Associative array of different media types related values.
     * scheme: regex to check against a media URL/filename;
     * id: index of the media ID in the matches;
     * glue: an optional string to append to the first ID if multiple ID's can be macthed in the same URL;
     * prefix: an optional string to prepend to the current ID.
     *
     * @var array
     * @example
     * protected static $mediaPatterns = array(
     *     'video' => array(
     *         'scheme' => '#^(http|https)://(www\.)?(youtube\.com/(watch\?v=|embed/|v/)|youtu\.be/)(([^&?/]+)?)#i',
     *         'id'     => '5',
     *         'glue'   => '&amp;',
     *     ),
     *     'list'  => array(
     *         'scheme' => '#^(http|https)://(www\.)?(youtube\.com/(watch\?v=|embed/|v/)|youtu\.be/)[\S]+list=([^&?/]+)?#i',
     *         'id'     => '5',
     *         'prefix' => 'list=',
     *     ),
     * );
     * @see getMediaPatterns(), setMediaInfos().
     */

    protected static $mediaPatterns = array();

    /**
     * Media related infos.
     *
     * @var array
     * @see setMediaInfos(), getMediaInfos().
     */

    protected $mediaInfos;

    /**
     * Player parmaters and their values.
     *
     * @var array
     * @see setParams(), getParams().
     */

    protected $params;

    /**
     * Preference names and their related values.
     * Names are not prefixed by the preferences related event.
     *
     * @var array
     * @see setPrefs(), getPrefs(), getPref().
     */

    protected $prefs;

    /**
     * Initial player parameters and related options.
     *
     * @var array
     * @example
     * protected static $iniParams = array(
     *     'size'  => array(
     *         'default' => 'large',
     *         'force'   => true,
     *         'valid'   => array('large', 'small'),
     *     ),
     * );
     *
     * Where 'size' is a player parameter and 'large' is its default value.
     * 'force' allows to set the parameter even if its value is the default one.
     * The 'valid' key accept an array of values or a string as an HTML input type.
     * @see getIniParams(), getTagAtts().
     */

    protected static $iniParams = array();

    /**
     * Strings sticking different player URL parts.
     *
     * @var array
     * @see setSrcGlue(), getSrcGlue(), resetSrcGlue(), getSrc().
     */

    protected static $srcGlue = array('/', '?', '&amp;');

    /**
     * Player label and labeltag
     *
     * @var array
     * @see setLabel(), getLabel().
     */

    protected $label;

    /**
     * Player wraptag and class
     *
     * @var array
     * @see setWrap(), getWrap().
     */

    protected $wrap;

    /**
     * Constructor.
     */

    final public function __construct()
    {
        self::setProvider();
        self::setPrefs();
    }

    /**
     * $label property getter.
     *
     * @return object $this
     */

    final public function setLabel($txt, $tag = '') {
        $this->label = array($txt, $tag);

        return $this;
    }

    /**
     * $label property getter.
     *
     * @return array
     */

    final protected function getLabel() {
        return $this->label;
    }

    /**
     * $wrap property getter.
     *
     * @return object $this
     */

    final public function setWrap($tag, $class = '') {
        $this->wrap = array($tag, $class);

        return $this;
    }

    /**
     * $wrap property getter.
     *
     * @return array
     */

    final protected function getWrap() {
        return $this->wrap;
    }

    /**
     * $media property setter.
     *
     * @return object $this.
     */

    final public function setMedia($value, $fallback = false)
    {
        $this->media = is_array($value) ? array_unique($value) : $value;
        $this->setMediaInfos($fallback);

        return $this;
    }

    /**
     * $media property getter.
     *
     * @return string|array
     */

    final public function getMedia()
    {
        return $this->media;
    }

    /**
     * $params property setter.
     *
     * @return object $this
     */

    public function setParams($nameVals = null)
    {
        $this->params = array();

        foreach (self::getIniParams() as $param => $infos) {
            $pref = $this->getPref($param);
            $default = is_array($infos) ? $infos['default'] : $infos;
            $att = str_replace('-', '_', $param);
            $value = isset($nameVals[$att]) ? $nameVals[$att] : '';

            // Add defined attribute values or modified preference values as player parameters.
            if ($value === '' && ($pref !== $default || isset($infos['force']))) {
                $this->params[$param] = str_replace('#', '', $pref); // Remove the hash from the color pref as a color type is used for the pref input.
            } elseif ($value !== '') {
                $validArray = isset($infos['valid']) && is_array($infos['valid']) ? $infos['valid'] : '';

                if (!$validArray || $validArray && in_array($value, $validArray)) {
                    $this->params[$param] = str_replace('#', '', $value); // Remove the hash in the color attribute just in case…
                } else {
                    trigger_error(
                        'Unknown attribute or preference value for "' . $att .
                        '". Valid values are: "' . implode('", "', $validArray) . '".'
                    );
                }
            }
        }

        return $this;
    }

    /**
     * $params property getter.
     *
     * @return array
     */

    public function getParams()
    {
        $this->params !== null ?: $this->setParams();

        return $this->params;
    }

    /**
     * $params property item getter.
     *
     * @param  string $name Parameter name.
     * @return string|array Parameter value or the $params full array.
     */

    final protected function getParam($name)
    {
        $params = $this->getParams();

        return isset($params[$name]) ? $params[$name] : null;
    }

    /**
     * $provider property setter.
     */

    final protected static function setProvider()
    {
        static::$provider = substr(strrchr(get_called_class(), '\\'), 1);
    }

    /**
     * $provider property getter.
     *
     * @return string
     */

    final public static function getProvider()
    {
        self::setProvider();

        return static::$provider;
    }

    /**
     * $script property getter.
     *
     * @param  bool  $wrap Whether to wrap to embed the script URL in a script tag or not;
     * @return string|null URL or HTML script tag; null if the property is not set.
     */

    final public static function getScript($wrap = false)
    {
        if (isset(static::$script)) {
            return $wrap ? '<script src="' . static::$script . '"></script>' : static::$script;
        }

        return null;
    }

    /**
     * $scriptEmbedded property getter.
     *
     * @return bool|null null if the $script property is not set.
     */

    final protected static function getScriptEmbedded()
    {
        return isset(static::$script) ? static::$scriptEmbedded : null;
    }

    /**
     * $iniDims property getter.
     *
     * @return array
     */

    final protected static function getIniDims()
    {
        return static::$iniDims;
    }

    /**
     * $iniParams property getter.
     *
     * @return array
     */

    final protected static function getIniParams()
    {
        return static::$iniParams;
    }

    /**
     * $mediaType property getter.
     *
     * @return string
     */

    final protected static function getMediaType()
    {
        return static::$mediaType;
    }


    /**
     * $mediaPatterns property getter.
     *
     * @return array
     */

    final protected static function getMediaPatterns()
    {
        if (array_key_exists('scheme', static::$mediaPatterns)) {
            return array(static::$mediaPatterns);
        }

        return static::$mediaPatterns;
    }

    /**
     * $srcBase property getter.
     *
     * @return string
     */

    final protected static function getSrcBase()
    {
        return static::$srcBase;
    }

    /**
     * $srcGlue property getter.
     *
     * @param integer $i Index of the $srcGlue value to get;
     * @return mixed Value of the $srcGlue item as string, or the $srcGlue array.
     */

    final protected static function getSrcGlue($i = null)
    {
        return $i ? static::$srcGlue[$i] : static::$srcGlue;
    }

    /**
     * $srcGlue property setter.
     *
     * @param integer $i     Index of the $srcGlue value to set;
     * @param string  $value Value of the $srcGlue item.
     */

    final protected static function setSrcGlue($i, $value)
    {
        static::$srcGlue[$i] = $value;
    }

    /**
     * Embed the provider script.
     */

    final public function embedScript()
    {
        if ($ob = ob_get_contents()) {
            ob_clean();

            echo str_replace(
                '</body>',
                self::getScript(true) . n . '</body>',
                $ob
            );

            static::$scriptEmbedded = true;
        }
    }

    /**
     * Collect provider prefs.
     *
     * @param  array $prefs Prefs collected provider after provider.
     * @return array Collected prefs merged with ones already provided.
     */

    final public static function getIniPrefs()
    {
        $prefs = array_merge(self::getIniDims(), self::getIniParams());
        $parsedPrefs = array();

        foreach ($prefs as $pref => $options) {
            is_array($options) ?: $options = array('default' => $options);
            $options['group'] = Player::getPlugin() . '_' . strtolower(self::getProvider());
            $parsedPrefs[$options['group'] . '_' . $pref] = $options;
        }

        return $parsedPrefs;
    }

    /**
     * $prefs getter.
     *
     * @return array
     */

    final protected function getPrefs()
    {
        return $this->prefs;
    }

    /**
     * $prefs setter.
     *
     * @return object this
     */

    final protected function setPrefs()
    {
        $event = Player::getPlugin() . '_' . strtolower(self::getProvider());
        $this->prefs = array();
        $prefRows = safe_rows_start(
            "name, val",
            'txp_prefs',
            "type = 1 AND event = '" . doSlash($event) . "'"
        );

        while ($prefRow = nextRow($prefRows)) {
            $this->prefs[str_replace($event . '_', '', $prefRow['name'])] = $prefRow['val'];
        }

        return $this;
    }

    /**
     * $prefs item getter.
     *
     * @param  array $name Preference name (without the event related prefix).
     * @return string The preference value.
     */

    final protected function getPref($name)
    {
        return $this->prefs[$name];
    }

    /**
     * Get a tag attributes.
     *
     * @param  string $tag      The plugin tag.
     * @param  array  $get_atts Stores attributes provider after provider.
     * @return array
     */

    final public static function getTagAtts($tag)
    {
        $atts = array_keys(array_merge(self::getIniDims(), self::getIniParams()));
        $parsedAtts = array();

        foreach ($atts as $att) {
            $parsedAtts[] = str_replace('-', '_', $att); // Underscore to hyphen in attribute names.
        }

        return $parsedAtts;
    }

    /**
     * Set the current media(s) infos.
     *
     * @param  bool  $fallback Whether to set fallback $mediaInfos values or not.
     * @return array
     */

    final protected function setMediaInfos($fallback = false)
    {
        $medias = $this->getMedia();
        !is_array($medias) ? $medias = array($medias) : '';
        $this->mediaInfos = array();

        foreach ($medias as $media) {
            $notId = preg_match('/([.][a-z]+)/', $media); // URL or filename.

            if ($notId) {
                $glue = null;

                // Check the URL or filename against defined $mediaPatterns property values.
                foreach (self::getMediaPatterns() as $pattern => $options) {
                    if (preg_match($options['scheme'], $media, $matches)) {
                        $prefix = isset($options['prefix']) ? $options['prefix'] : '';

                        if (!array_key_exists($media, $this->mediaInfos)) {
                            $this->mediaInfos[$media] = array(
                                'id'      => $matches[$options['id']],
                                'uri'     => $prefix . $matches[$options['id']],
                                'pattern' => $pattern,
                            );

                            if (!isset($options['glue'])) {
                                break;
                            } else { // Bandcamp and Youtube, at least, accept multiple matches.
                                $glue = $options['glue'];
                            }
                        } else {
                            $this->mediaInfos[$media]['uri'] .= $glue . $prefix . $matches[$options['id']];
                            $this->mediaInfos[$media]['pattern'] = $pattern;
                        }
                    }
                }
            } elseif ($fallback) {
                $this->mediaInfos[$media] = array(
                    'uri' => $media,
                );
            }

            if (method_exists($this, 'resetSrcGlue') && array_key_exists($media, $this->mediaInfos)) {
                $this->resetSrcGlue($media);
            }
        }

        return $this;
    }

    /**
     * $mediaInfos getter.
     *
     * @return array
     */

    final public function getMediaInfos($fallback = false)
    {
        $this->mediaInfos or $this->setMediaInfos($fallback);

        return $this->mediaInfos;
    }

    /**
     * Get the player size.
     *
     * @return array 'width' and 'height' and 'responsive' associated values — Height could be not set (i.e. HTML5 audio player).
     * @TODO override the HTML audio player related method to remove $height + $ratio.
     */

    final public function setDims(
        $width = null,
        $height = null,
        $ratio = null,
        $responsive = null
    ) {
        // Get dimensions from attributes, or fallback to preferences.
        $atts = compact('width', 'height', 'ratio');

        foreach (self::getIniDims() as $dim => $value) {
            if ($dim !== 'responsive') {
                is_bool($atts[$dim]) ? $atts[$dim] = '00' : '';

                $$dim = str_replace(' ', '', $atts[$dim] ? $atts[$dim] : $this->getPref($dim));

                if ($dim !== 'ratio') {
                    $dUnit = $dim[0] . 'Unit';
                    preg_match("/\D+/", $$dim, $$dUnit) ? $$dUnit = $$dUnit[0] : '';
                    $$dim = (int) $$dim;
                }
            }
        }

        // Work out the provided ratio.
        $aspect = null;

        if (!empty($ratio)) {
            if (preg_match("/(\d+):(\d+)/", $ratio, $matches)) {
                list(, $wRatio, $hRatio) = $matches;
            }

            if (empty($wRatio) || empty($hRatio)) {
                $aspect = 1.77777777778;

                trigger_error(gtxt(
                    'oui_player_invalid_ratio',
                    array('{ratio}' => $ratio)
                ));
            } else {
                $aspect = $wRatio / $hRatio;
            }
        }

        // Calculate player width and/or height.
        if ($responsive === null) {
            $responsive = $this->getPref('responsive') === 'true';
        } else {
            $responsive = $responsive ? true : false;
        }


        if ($responsive) {
            if ($aspect) {
                $height = 1 / $aspect * 100 . '%';
            } elseif (isset($height)) {
                if ($width && $height) {
                    $wUnit === $hUnit ? $height = $height / $width * 100 . '%' : '';
                } else {
                    trigger_error(gtxt('undefined_player_size'));
                }
            }

            $width = '100%';
        } else {
            if (isset($height) && (!$width || !$height)) {
                if ($aspect) {
                    if ($width) {
                        $height = $width / $aspect;
                        $wUnit ? $height .= $wUnit : '';
                    } else {
                        $width = $height * $aspect;
                        $hUnit ? $width .= $hUnit : '';
                    }
                } else {
                    trigger_error(gtxt('undefined_player_size'));
                }
            }
        }

        // Re-append unit if needed.
        is_int($width) && $wUnit && $wUnit !== 'px' ? $width .= $wUnit : '';

        if (isset($height)) {
            $responsive && !$hUnit ? $hUnit = 'px' : '';
            is_int($height) && $hUnit && ($responsive || $hUnit !== 'px') ? $height .= $hUnit : '';
        }

        $this->dims = compact('width', 'height', 'responsive');

        return $this;
    }

    /**
     * $dims getter.
     *
     * @return array
     */

    final public function getDims()
    {
        $this->dims ?: $this->setDims();

        return $this->dims;
    }

    /**
     * Build the player src value.
     *
     * @return string
     */

    final protected function getSrc()
    {
        $media = $this->getMedia();

        if (!$media) {
            trigger_error('Nothing to play');
            return;
        }

        $media = $this->getMediaInfos(true)[$media]['uri'];
        $srcGlue = self::getSrcGlue();
        $src = self::getSrcBase() . $srcGlue[0] . $media; // Stick player URL and ID.

        // Stick defined player parameters.
        $params = $this->getParams();

        if (!empty($params)) {
            $joint = strpos($src, $srcGlue[1]) ? $srcGlue[2] : $srcGlue[1]; // Avoid repeated srcGlue elements (interrogation marks).
            $src .= $joint . http_build_query($params, '', $srcGlue[2]); // Stick.
        }

        return $src;
    }

    /**
     * Generate the player code.
     *
     * @return string HTML
     */

    public function getHTML() {
        // Embed the provider related $script if needed.
        if (self::getScript() && !self::getScriptEmbedded()) {
            register_callback(array($this, 'embedScript'), 'textpattern_end');
        }

        $src = $this->getSrc();

        if (!$src) {
            return;
        }

        $dims = $this->getDims();

        extract($dims);

        // Define responsive related styles.
        $style = 'style="border: none';
        $wrapStyle = '';

        if ($responsive) {
            $style .= '; position: absolute; top: 0; left: 0; width: 100%; height: 100%';
            $wrapStyle .= 'style="position: relative; padding-bottom:' . $height . '; height: 0; overflow: hidden"';
            $width = $height = false;
        } else {
            foreach (array('width', 'height') as $dim) {
                if (is_string($$dim)) {
                    $style .= '; ' . $dim . ':' . $$dim;
                    $$dim = false;
                }
            }
        }

        $style .= '"';

        // Build the player code.
        $player = sprintf(
            '<iframe src="%s"%s%s %s %s></iframe>',
            $src,
            !$width ? '' : ' width="' . $width . '"',
            !$height ? '' : ' height="' . $height . '"',
            $style,
            'allowfullscreen'
        );

        list($wraptag, $class) = $this->getWrap();
        list($label, $labeltag) = $this->getLabel();

        $wrapStyle && !$wraptag ? $wraptag = 'div' : '';
        $wraptag ? $player = n . $player . n : '';

        $out = doLabel($label, $labeltag) . n . doTag($player, $wraptag, $class, $wrapStyle);

        return $out;
    }

    /**
     * Render the player.
     */

    final public function render()
    {
        return pluggable_ui(Player::getPlugin(), strtolower(self::getProvider()), $this->getHTML(), $this);
    }

    /**
     * Main provider related tag callback method.
     */

    final public static function renderPlayer($atts)
    {
        $atts['provider'] = self::getProvider();

        return \Txp::get('\Oui\Player')->renderPlayer($atts);
    }

    /**
     * Conditional provider related tag callback method.
     */

    final public static function renderIfPlayer($atts, $thing)
    {
        $atts['provider'] = self::getProvider();

        return \Txp::get('\Oui\Player')->renderIfPlayer($atts, $thing);
    }
}
