class Follow
  include Neo4j::ActiveRel
  include Neo4j::Timestamps

  creates_unique

  from_class :User
  to_class :User
  type :follows

  property :following, type: Boolean, default: false
  validates :following, presence: true
end

