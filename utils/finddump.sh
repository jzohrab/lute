# Find the string "dump" in src.

# set -e
# set -x

find src -name "*.*" -print0 | xargs -0 grep -i dump 2>/dev/null | grep -v "src/Utils/OneTimeJobs" | grep -v "// dump" | grep -v "// var_dump" | grep -iv "mysqldump" > zzfound_dumps.txt

find src -name "*.php" -print0 | xargs -0 grep -i echo 2>/dev/null | grep -v "src/Utils/OneTimeJobs" | grep -v "// echo" >> zzfound_dumps.txt

count=`cat zzfound_dumps.txt | wc -l`

if [[ $count -ne 0 ]]; then
    echo
    echo "dump or echo found!"
    echo
    cat zzfound_dumps.txt
    echo
    echo "Clean these up before shipping master."
    echo
    rm zzfound_dumps.txt
    exit 1
fi

echo "No dump statements found."
rm zzfound_dumps.txt
