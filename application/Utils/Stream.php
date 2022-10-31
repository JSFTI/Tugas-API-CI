<?php

namespace App\Utils;

/**
 * Modified from: https://gist.github.com/jas-/5c3fdc26fedd11cb9fb5#file-class-stream-php
 */
class Stream
{
    /**
     * @abstract Raw input stream
     */
    protected $input;

    protected $post;
    protected $files;

    /**
     * @function __construct
     *
     * @param array $data stream
     */
    public function __construct($stream)
    {
        $this->input = $stream;

        $boundary = $this->boundary();

        if (!strlen($boundary)) {
            $data = [
                'post' => $this->parse(),
                'files' => []
            ];
        } else {
            $blocks = $this->split($boundary);

            $data = $this->blocks($blocks);
            $this->post = $data['post'];
            $this->files = $data['files'];
        }
    }

    public function get_post(){
        return $this->post;
    }

    public function get_files(){
        return $this->files;
    }

    /**
     * @function boundary
     * @returns string
     */
    private function boundary()
    {
        if(!isset($_SERVER['CONTENT_TYPE'])) {
            return null;
        }

        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        return $matches[1];
    }

    /**
     * @function parse
     * @returns array
     */
    private function parse()
    {
        parse_str(urldecode($this->input), $result);
        return $result;
    }

    /**
     * @function split
     * @param $boundary string
     * @returns array
     */
    private function split($boundary)
    {
        $result = preg_split("/-+$boundary/", $this->input);
        array_pop($result);
        return $result;
    }

    /**
     * @function blocks
     * @param $array array
     * @returns array
     */
    private function blocks($array)
    {
        $results = [
            'post' => [],
            'files' => []
        ];

        foreach($array as $key => $value)
        {
            if (empty($value))
                continue;

            $block = $this->decide($value);

            if (count($block['post']) > 0)
                array_push($results['post'], $block['post']);

            if (count($block['files']) > 0)
                array_push($results['files'], $block['files']);
        }

        return $this->merge($results);
    }

    /**
     * @function decide
     * @param $string string
     * @returns array
     */
    private function decide($string)
    {
        if (strpos($string, 'application/octet-stream') !== FALSE)
        {
            return [
                'post' => $this->file($string),
                'files' => []
            ];
        }

        if (strpos($string, 'filename') !== FALSE)
        {
            
            return [
                'post' => [],
                'files' => $this->file_stream($string)
            ];
        }

        return [
            'post' => $this->post($string),
            'files' => []
        ];
    }

    /**
     * @function file
     *
     * @param $string
     *
     * @return array
     */
    private function file($string)
    {
        preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $string, $match);
        return [
            $match[1] => (!empty($match[2]) ? $match[2] : '')
        ];
    }

    /**
     * @function file_stream
     *
     * @param $string
     *
     * @return array
     */
    private function file_stream($string)
    {
        $data = [];

        preg_match('/name=\"([^\"]*)\"; filename=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $string, $match);

        preg_match('/Content-Type: (.*)?/', $match[3], $mime);

        $image = preg_replace('/Content-Type: (.*)[^\n\r]/', '', $match[3]);

        $path = sys_get_temp_dir().'/php'.substr(sha1(rand()), 0, 6);

        $err = file_put_contents($path, ltrim($image));

        if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
            $index = $tmp[1];
        } else {
            $index = $match[1];
        }

        $data[$index]['name'][] = $match[2];
        $data[$index]['type'][] = $mime[1];
        $data[$index]['tmp_name'][] = $path;
        $data[$index]['error'][] = ($err === FALSE) ? $err : 0;
        $data[$index]['size'][] = filesize($path);

        return $data;
    }

    /**
     * @function post
     *
     * @param $string
     *
     * @return array
     */
    private function post($string)
    {
        $data = [];

        preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $string, $match);

        if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
            $data[$tmp[1]][] = (!empty($match[2]) ? $match[2] : '');
        } else {
            $data[$match[1]] = (!empty($match[2]) ? $match[2] : '');
        }

        return $data;
    }

    /**
     * @function merge
     * @param $array array
     *
     * Ugly ugly ugly
     *
     * @returns array
     */
    private function merge($array)
    {
        $results = [
            'post' => [],
            'files' => []
        ];

        if (count($array['post']) > 0) {
            foreach($array['post'] as $key => $value) {
                foreach($value as $k => $v) {
                    if (is_array($v)) {
                        foreach($v as $kk => $vv) {
                            $results['post'][$k][] = $vv;
                        }
                    } else {
                        $results['post'][$k] = $v;
                    }
                }
            }
        }

        // This methods adapts to native PHP $_FILES format
        if (count($array['files']) > 0) {
            foreach($array['files'] as $key => $value) {
                foreach($value as $k => $v) {
                    if (is_array($v)) {
                        foreach($v as $kk => $vv) {
                            if(is_array($vv) && (count($vv) === 1) && !(array_key_exists($k, $results['files']) && array_key_exists($kk, $results['files'][$k]))) {
                                $results['files'][$k][$kk] = $vv[0];
                            } else {
                                if(!is_array($results['files'][$k][$kk])){
                                    $results['files'][$k][$kk] = [$results['files'][$k][$kk]];
                                }
                                $results['files'][$k][$kk][] = $vv[0];
                            }
                        }
                    } else {
                        $results['files'][$k][$key] = $v;
                    }
                }
            }
        }

        // This method groups file metadata to one array
        /**
         * [
         *   'doc' => [
         *     [
         *       ..metadata
         *     ],
         *     [
         *       ..metadata
         *     ],
         *   ]
         * ]
         */
        // if (count($array['files']) > 0) {
        //     foreach($array['files'] as $key => $value) {
        //         foreach($value as $k => $v) {
        //             if(array_key_exists($k, $results['files'])){
        //                 $store = $results['files'][$k];

        //                 $results['files'][$k] = [$store];
        //             }
        //             if (is_array($v)) {
        //                 $arr = [];
        //                 foreach($v as $kk => $vv) {
        //                     if(is_array($vv) && (count($vv) === 1)) {
        //                         $arr[$kk] = $vv[0];
        //                     } else {
        //                         $arr[$kk][] = $vv[0];
        //                     }
        //                 }
        //                 if(!array_key_exists($k, $results['files'])){
        //                     $results['files'][$k] = $arr;
        //                 } else {
        //                     $results['files'][$k][] = $arr;
        //                 }
        //             } else {
        //                 $results['files'][$k][$key] = $v;
        //             }
        //         }
        //     }
        // }

        return $results;
    }
}

?>