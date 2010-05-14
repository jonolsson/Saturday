<a href="url/new/">New</a>
<ul>
<? foreach($this->nameplural as $name) { ?>
    <li>
        <a href="url/show/<?=$name->id?>">
            fields
        </a>
        <a href="url/edit/<?=$name->id?>">Edit</a> / <a href="url/destroy/<?=$name->id?>">Delete</a>
    </li>
<? } ?>
</ul>
