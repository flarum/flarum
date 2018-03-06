# Configuration

`Zend\Escaper\Escaper` has only one configuration option available, and that is
the encoding to be used by the `Escaper` instance.

The default encoding is **utf-8**. Other supported encodings are:

- iso-8859-1
- iso-8859-5
- iso-8859-15
- cp866, ibm866, 866
- cp1251, windows-1251
- cp1252, windows-1252
- koi8-r, koi8-ru
- big5, big5-hkscs, 950, gb2312, 936
- shift\_jis, sjis, sjis-win, cp932
- eucjp, eucjp-win
- macroman

If an unsupported encoding is passed to `Zend\Escaper\Escaper`, a
`Zend\Escaper\Exception\InvalidArgumentException` will be thrown.
