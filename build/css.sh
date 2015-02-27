#!/bin/bash
mydir=`dirname $0`
lessc "$mydir/css/translator.less" "$mydir/../integrated_localization/css/translator.css"
