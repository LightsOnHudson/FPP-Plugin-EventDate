#!/bin/bash
pushd $(dirname $(which $0))
target_PWD=$(readlink -f .)
exec /opt/fpp/scripts/update_plugin ${target_PWD##*/}
/bin/cp /home/fpp/media/plugins/EventDate/RUN-COUNTDOWN-SCRIPT.php /home/fpp/media/scripts
popd
