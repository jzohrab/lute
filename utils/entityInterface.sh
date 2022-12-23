classname="$1"
if [[ "$classname" -eq "" ]]; then
    echo "Need class name."
fi;

cat src/Entity/${classname}.php | grep public
