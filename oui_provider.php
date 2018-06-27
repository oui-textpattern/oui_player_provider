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

namespace Oui\Player {

    abstract class Provider
    {
        /**
         * The provider name set from the class name.
         *
         * @var string
         * @see setProvider(), getProvider().
         */

        protected static $provider;

        /**
         * The value provided through the play attribute.
         *
         * @var string
         * @see setPlay(), getPlay().
         */

        protected $play;

        /**
         * @var array
         * @see setInfos(), getInfos().
         */

        protected $infos;

        /**
         * Attributes and their values.
         *
         * @var array
         * @see setConfig(), getConfig().
         */

        protected $config;

        /**
         * Associative array of different media types related values.
         * scheme: regex to check against a media URL/filename;
         * id: index of the media ID in the matches;
         * glue: an optional string to append to the first ID if multiple ID's can be macthed in the same URL;
         * prefix: an optional string to prepend to the current ID.
         *
         * @var array
         * @example
         * protected static $patterns = array(
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
         * @see getPatterns(), setInfos().
         */

        protected static $patterns = array();

        /**
         * The player base path.
         *
         * @var string
         * @example '//www.youtube-nocookie.com/'
         * @see getSrc().
         */

        protected static $src;

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
         * @see getDims(), getSize().
         */

        protected static $dims = array(
            'width'    => array(
                'default' => '640',
            ),
            'height'   => array(
                'default' => '',
            ),
            'ratio'    => array(
                'default' => '16:9',
            ),
        );

        /**
         * Player parameters and related options/values.
         *
         * @var array
         * @example
         * protected static $params = array(
         *     'size'  => array(
         *         'default' => 'large',
         *         'force'   => true,
         *         'valid'   => array('large', 'small'),
         *     ),
         * );
         *
         * Where 'size' is a player parameter and 'large' is its default value.
         * 'force' allows to set the parameter even if its value is the default one.
         * The 'valid' key accept an array of values or a type of values as an HTML input type.
         * @see getParams(), getPlayerParams().
         */

        protected static $params = array();

        /**
         * Strings sticking different player URL parts.
         *
         * @var array
         * @see setGlue(), getGlue(), resetGlue(), getPlaySrc().
         */

        protected static $glue = array('/', '?', '&amp;');

        /**
         * Caches the class instance.
         *
         * @var object
         * @see getInstance().
         */

        private static $instance = null;

        /**
         * Singleton.
         */

        public static function getInstance()
        {
            $class = get_called_class();

            if (!isset(self::$instance[$class])) {
                self::$instance[$class] = new static();
            }

            return self::$instance[$class];
        }

        /**
         * Constructor.
         * Set the $provider property.
         */

        protected function __construct()
        {
            self::setProvider();
        }

        /**
         * $responsive property getter.
         *
         * @return bool
         */

        protected function getResponsive() {
            $att = $this->getConfig('responsive');

            return $att ? $att === 'true' ? true : false : get_pref('oui_player_responsive') === 'true';
        }

        /**
         * $play property setter.
         *
         * @return object $this.
         */

        public function setPlay($value, $fallback = false)
        {
            $this->play = $value;
            $infos = $this->getInfos();

            if (!$infos || !array_key_exists($value, $infos)) {
                $this->setInfos($fallback);
            }

            return $this;
        }

        /**
         * $play property getter.
         *
         * @return array
         */

        protected function getPlay()
        {
            return explode(', ', $this->play);
        }

        /**
         * $config property setter.
         *
         * @return object $this
         */

        public function setConfig($value)
        {
            $this->config = $value;

            return $this;
        }

        /**
         * $config property getter.
         *
         * @param  string $att Attribute name.
         * @return mixed       Attribute value or the $config full array.
         */

        protected function getConfig($att = null)
        {
            return $att ? $this->config[$att] : $this->config;
        }

        /**
         * $provider property setter.
         */

        protected static function setProvider()
        {
            static::$provider = substr(strrchr(get_called_class(), '\\'), 1);
        }

        /**
         * $provider property getter.
         *
         * @return array
         */

        public static function getProvider()
        {
            self::setProvider();

            return array(static::$provider);
        }

        /**
         * $script property getter.
         *
         * @param  bool  $wrap Whether to wrap to embed the script URL in a script tag or not;
         * @return mixed       URL or HTML script tag.
         */

        protected static function getScript($wrap = false)
        {
            if (isset(static::$script)) {
                return $wrap ? '<script src="' . static::$script . '"></script>' : static::$script;
            }

            return false;
        }

        /**
         * $scriptEmbedded property getter.
         *
         * @return bool
         */

        protected static function getScriptEmbedded()
        {
            return static::$scriptEmbedded;
        }

        /**
         * $dims property getter.
         *
         * @return array
         */

        protected static function getDims()
        {
            return static::$dims;
        }

        /**
         * $params property getter.
         *
         * @return array
         */

        protected static function getParams()
        {
            return static::$params;
        }

        /**
         * $patterns property getter.
         *
         * @return array
         */

        protected static function getPatterns()
        {
            return static::$patterns;
        }

        /**
         * $src property getter.
         *
         * @return string
         */

        protected static function getSrc()
        {
            return static::$src;
        }

        /**
         * $glue property getter.
         *
         * @param  integer $i     Index of the $glue value to get;
         * @return mixed          Value of the $glue item as string, or the $glue array.
         */

        protected static function getGlue($i = null)
        {
            return $i ? static::$glue[$i] : static::$glue;
        }

        /**
         * $glue property setter.
         *
         * @param integer $i     Index of the $glue value to set;
         * @param string  $value Value of the $glue item.
         */

        protected static function setGlue($i, $value)
        {
            static::$glue[$i] = $value;
        }

        /**
         * Embed the provider script.
         */

        public function embedScript()
        {
            if ($ob = ob_get_contents()) {
                ob_clean();

                echo str_replace(
                    '</body>',
                    self::getScript(true) . n . '</body>',
                    $ob
                );
            }
        }

        /**
         * Collect provider prefs.
         *
         * @param  array $prefs Prefs collected provider after provider.
         * @return array Collected prefs merged with ones already provided.
         */

        public static function getPrefs($prefs)
        {
            $merge_prefs = array_merge(self::getDims(), self::getParams());

            foreach ($merge_prefs as $pref => $options) {
                $options['group'] = strtolower(str_replace('\\', '_', get_called_class()));
                $prefs[$options['group'] . '_' . $pref] = $options;
            }

            return $prefs;
        }

        /**
         * Get a tag attributes.
         *
         * @param  string $tag      The plugin tag.
         * @param  array  $get_atts Stores attributes provider after provider.
         * @return array
         */

        public static function getAtts($tag, $get_atts)
        {
            $atts = array_merge(self::getDims(), self::getParams());

            // Replace any underscore with an hyphen.
            foreach ($atts as $att => $options) {
                $get_atts[str_replace('-', '_', $att)] = '';
            }

            return $get_atts;
        }

        /**
         * Set the current media(s) infos.
         *
         * @param  bool  $fallback Whether to set fallback $infos values or not.
         * @return array
         */

        public function setInfos($fallback = false)
        {
            $this->infos = array();

            foreach ($this->getPlay() as $play) {
                $notId = preg_match('/([.][a-z]+)/', $play); // URL or filename.

                if ($notId) {
                    $glue = null;

                    // Check the URL or filename against defined $patterns property values.
                    foreach (self::getPatterns() as $pattern => $options) {
                        if (preg_match($options['scheme'], $play, $matches)) {
                            $prefix = isset($options['prefix']) ? $options['prefix'] : '';

                            if (!array_key_exists($play, $this->infos)) {
                                $this->infos[$play] = array(
                                    'play' => $prefix . $matches[$options['id']],
                                    'type' => $pattern,
                                );

                                // Bandcamp and Youtube, et least, accept multiple matches.
                                if (!isset($options['glue'])) {
                                    break;
                                } else {
                                    $glue = $options['glue'];
                                }
                            } else {
                                $this->infos[$play]['play'] .= $glue . $prefix . $matches[$options['id']];
                                $this->infos[$play]['type'] = $pattern;
                            }
                        }
                    }
                } elseif ($fallback) {
                    $this->infos[$play] = array(
                        'play' => $play,
                        'type' => 'id',
                    );
                }

                if (method_exists($this, 'resetGlue') && array_key_exists($play, $this->infos)) {
                    $this->resetGlue($play);
                }
            }


            return $this;
        }

        /**
         * Get the infos property.
         *
         * @return array
         */

        public function getInfos()
        {
            return $this->infos;
        }

        /**
         * Get the modified player parameters
         * from the plugin tag attributes
         * or from the plugin prefs.
         *
         * @return array Parameters and their values.
         */

        protected function getPlayerParams()
        {
            $config = $this->getConfig();
            $params = array();

            foreach (self::getParams() as $param => $infos) {
                $pref = get_pref(strtolower(str_replace('\\', '_', get_class($this))) . '_' . $param);
                $default = $infos['default'];
                $att = str_replace('-', '_', $param);
                $value = isset($config[$att]) ? $config[$att] : '';

                // Add defined attribute values or modified preference values as player parameters.
                if ($value === '' && ($pref !== $default || isset($infos['force']))) {
                    $params[] = $param . '=' . str_replace('#', '', $pref); // Remove the hash from the color pref as a color type is used for the pref input.
                } elseif ($value !== '') {
                    $params[] = $param . '=' . str_replace('#', '', $value); // Remove the hash in the color attribute just in case…
                }
            }

            return $params;
        }

        /**
         * Get the player size.
         * Height and ratio can be not set (i.e. HTML5 audio player).
         *
         * @return array Width, (height) and (pourcent) keys and their values.
         */

        protected function getSize()
        {
            // Get dimensions from attributes, or fallback to preferences.
            $config = $this->getConfig();

            foreach (self::getDims() as $dim => $infos) {
                $pref = get_pref(strtolower(str_replace('\\', '_', get_class($this))) . '_' . $dim);
                $att = isset($config[$dim]) ? $config[$dim] : '';

                if ($att === true || $att === 'false') {
                    $$dim = 0;
                } elseif ($att) {
                    $$dim = $att;
                } else {
                    $$dim = $pref;
                }
            }

            // Work out the provided ratio.
            if (!empty($ratio)) {
                preg_match("/(\d+):(\d+)/", $ratio, $matches);

                if ($matches && $matches[1]!=0 && $matches[2]!=0) {
                    $aspect = $matches[1] / $matches[2]; // Get the ratio as a decimal.
                    $pourcent = 1 / $aspect * 100 . '%'; // Get the height as a pourcentage of the width for responsive rendering.
                } else {
                    trigger_error(gtxt('invalid_player_ratio'));
                }
            }

            // Calculate palyer width and/or height.
            $responsive = $this->getResponsive();

            if ($responsive) {
                if (!empty($ratio)) {
                    $width = $height = '100%';
                } elseif (isset($height)) {
                    if ($width && $height) {
                        preg_match("/(\D+)/", $width, $widthUnit);
                        preg_match("/(\D+)/", $height, $heightUnit);

                        if ($widthUnit && $heightUnit && $widthUnit === $heightUnit || !$widthUnit && !$heightUnit) {
                            $pourcent = (int) $height / (int) $width * 100 . '%';
                            $width = $height = '100%';
                        } elseif ($width === '100%' && !$heightUnit) {
                            $pourcent = $height . 'px';
                        }
                    } else {
                        trigger_error(gtxt('undefined_player_size'));
                    }
                } else {
                    $width = '100%';
                }
            } else {
                if (isset($height) && (!$width || !$height)) {
                    if ($ratio) {
                        if ($width) {
                            $height = $width / $aspect;
                            preg_match("/(\D+)/", $width, $unit);
                            isset($unit[0]) ? $height .= $unit[0] : '';
                        } else {
                            $width = $height * $aspect;
                            preg_match("/(\D+)/", $height, $unit);
                            isset($unit[0]) ? $width .= $unit[0] : '';
                        }
                    } else {
                        trigger_error(gtxt('undefined_player_size'));
                    }
                }
            }

            return compact('width', 'height', 'pourcent');
        }

        /**
         * Whether the $play property value is a provider related URL or not.
         *
         * @return bool
         */

        public function isValid()
        {
            return $this->getInfos();
        }

        /**
         * Build the player src value.
         *
         * @return string
         */

        protected function getPlaySrc()
        {
            $play = $this->getInfos()[$this->getPlay()[0]]['play'];
            $glue = self::getGlue();
            $src = self::getSrc() . $glue[0] . $play; // Stick player URL and ID.

            // Stick defined player parameters.
            $params = $this->getPlayerParams();

            if (!empty($params)) {
                $joint = strpos($src, $glue[1]) ? $glue[2] : $glue[1]; // Avoid repeated glue elements (interrogation marks).
                $src .= $joint . implode($glue[2], $params); // Stick.
            }

            return $src;
        }

        /**
         * Generate the player.
         *
         * @param  string $wraptag HTML wraptag name;
         * @param  string $class   Class name to apply to the wraptag.
         * @return HTML
         */

        public function getPlayer($wraptag = null, $class = null)
        {
            // Embed the provider related $script if needed.
            if (self::getScript() && !self::getScriptEmbedded()) {
                register_callback(array($this, 'embedScript'), 'textpattern_end');
                static::$scriptEmbedded = true;
            }

            $src = $this->getPlaySrc();
            $dims = $this->getSize();

            extract($dims);

            // Define responsive related styles.
            $style = '';
            $wrapstyle = '';

            if ($this->getResponsive()) {
                $style .= ' style="position: absolute; top: 0; left: 0" ';
                $wrapstyle .= 'style="position: relative; padding-bottom:' . $pourcent . '; height: 0; overflow: hidden"';
                $wraptag or $wraptag = 'div';
            }

            // Build the player code.
            $player = sprintf(
                '<iframe src="%s" width="%s" height="%s"%s%s></iframe>',
                $this->getPlaySrc(),
                $width,
                $height,
                $style,
                ' frameborder="0" allowfullscreen'
            );

            return ($wraptag) ? doTag($player, $wraptag, $class, $wrapstyle) : $player;
        }
    }
}